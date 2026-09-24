<?php
/**
 * Contact form endpoint — the imported contact template posts to
 * admin-post.php?action=arc_st_contact; this handler validates, sanitizes
 * and mails the submission, then redirects back with a status flag.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single-submission handler (security: every field sanitized server-side).
 */
final class Arc_ST_Contact {

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'admin_post_arc_st_contact', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_arc_st_contact', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_arc_st_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_nopriv_arc_st_subscribe', array( __CLASS__, 'handle_subscribe' ) );
	}

	/**
	 * Field map: POST name => label.
	 *
	 * @return array
	 */
	private static function fields() {
		return array(
			'first-name' => 'First Name',
			'last-name'  => 'Last Name',
			'company'    => 'Company',
			'phone'      => 'Phone',
			'work-email' => 'Work Email',
			'need'       => 'Needs help with',
			'count'      => 'People needed',
			'when'       => 'Timeline',
			'details'    => 'Details',
		);
	}

	/**
	 * Handles the submission.
	 */
	public static function handle() {
		$referer = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		$fail = function () use ( $referer ) {
			wp_safe_redirect( add_query_arg( 'arc_contact', 'error', $referer ) . '#contact-form' );
			exit;
		};

		// No nonce check — public form, nonces break under page caching.
		// Honeypot — pretend success but send nothing.
		if ( ! empty( $_POST['arc_st_hp'] ) ) {
			wp_safe_redirect( add_query_arg( 'arc_contact', 'sent', $referer ) . '#contact-form' );
			exit;
		}

		// Rate limit per IP — defense in depth against scripted spam/DoS since
		// there is no nonce (see comment above).
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			$rl_key = 'arc_st_cf_' . md5( $ip );
			if ( get_transient( $rl_key ) ) {
				$fail();
			}
			set_transient( $rl_key, 1, MINUTE_IN_SECONDS );
		}

		// GDPR/privacy consent — required when the admin configured a text.
		if ( '' !== (string) get_option( 'arc_st_consent_text', '' ) && empty( $_POST['arc_st_consent'] ) ) {
			$fail();
		}

		$values = array();
		foreach ( self::fields() as $name => $label ) {
			$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
			// Cap length before sanitizing so oversized payloads never reach
			// the mail body regardless of field type.
			$raw = mb_substr( (string) $raw, 0, 'details' === $name ? 5000 : 500 );
			if ( 'work-email' === $name ) {
				$values[ $name ] = sanitize_email( $raw );
			} elseif ( 'details' === $name ) {
				$values[ $name ] = sanitize_textarea_field( $raw );
			} else {
				$values[ $name ] = sanitize_text_field( $raw );
			}
		}

		if ( '' === $values['first-name'] || '' === $values['last-name']
			|| '' === $values['work-email'] || ! is_email( $values['work-email'] ) ) {
			$fail();
		}

		$subject = sprintf(
			/* translators: %s: site name. */
			__( '[%s] New contact request', 'arc-starter-templates' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$rows = array();
		foreach ( self::fields() as $name => $label ) {
			if ( '' !== $values[ $name ] ) {
				$rows[ $label ] = nl2br( esc_html( $values[ $name ] ) );
			}
		}

		$meta = sprintf( 'Sent: %s', current_time( 'mysql' ) );
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$meta .= ' · IP: ' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$content = arc_mail_block(
			arc_mail_heading( __( 'New contact request', 'arc-starter-templates' ) )
			. arc_mail_kv( $rows )
			. '<p style="margin:16px 0 0;color:#94a3b8;font-size:12px">' . esc_html( $meta ) . '</p>'
		);

		$to      = get_option( 'arc_st_contact_email' );
		$to      = ( $to && is_email( $to ) ) ? $to : get_option( 'admin_email' );
		$to      = apply_filters( 'arc_st_contact_recipient', $to );
		// Strip characters that could malform the header when building the display name.
		$display_name = preg_replace( '/[<>"\r\n]/', '', $values['first-name'] . ' ' . $values['last-name'] );
		$headers       = array( 'Reply-To: ' . $display_name . ' <' . $values['work-email'] . '>' );

		// Persist first: the entry must survive even if wp_mail fails.
		Arc_ST_Entries::insert(
			'contact',
			$values['first-name'] . ' ' . $values['last-name'],
			$values['work-email'],
			$values,
			$referer
		);

		$sent = arc_mail( $to, $subject, $content, array( 'kicker' => __( 'Contact form', 'arc-starter-templates' ) ), $headers );

		wp_safe_redirect( add_query_arg( 'arc_contact', $sent ? 'sent' : 'error', $referer ) . '#contact-form' );
		exit;
	}

	/**
	 * Newsletter/notify form endpoint (admin-post.php?action=arc_st_subscribe).
	 * Same anti-spam posture as the contact handler: honeypot + per-IP
	 * rate limit + optional consent checkbox.
	 */
	public static function handle_subscribe() {
		$referer = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		$done = function ( $status ) use ( $referer ) {
			wp_safe_redirect( add_query_arg( 'arc_subscribe', $status, $referer ) . '#subscribe' );
			exit;
		};

		// Honeypot — pretend success but store nothing.
		if ( ! empty( $_POST['arc_st_hp'] ) ) {
			$done( 'sent' );
		}

		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			$rl_key = 'arc_st_sb_' . md5( $ip );
			if ( get_transient( $rl_key ) ) {
				$done( 'error' );
			}
			set_transient( $rl_key, 1, MINUTE_IN_SECONDS );
		}

		if ( '' !== (string) get_option( 'arc_st_consent_text', '' ) && empty( $_POST['arc_st_consent'] ) ) {
			$done( 'error' );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! $email || ! is_email( $email ) ) {
			$done( 'error' );
		}

		Arc_ST_Entries::insert( 'subscribe', '', $email, array( 'email' => $email ), $referer );
		$done( 'sent' );
	}
}
