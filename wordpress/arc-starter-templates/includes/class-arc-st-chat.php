<?php
/**
 * Floating chat bot — a small assistant rendered by every imported template
 * page (and the plugin previews). Answers come from an editable set of
 * keyword => reply pairs stored in an option; every exchange is logged to a
 * dedicated table so the admin can monitor conversations from the "Chat Bot"
 * screen under the plugin menu.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chat settings, REST API and admin screen.
 */
final class Arc_ST_Chat {

	const TABLE  = 'arc_st_chat_logs';
	const DBV    = 'arc_st_chat_db';
	const OPTION = 'arc_st_chat';
	const PAGE   = 'arc-st-chat';

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
		add_action( 'admin_post_arc_st_chat_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_arc_st_chat_clear', array( __CLASS__, 'clear' ) );
		add_action( 'admin_post_arc_st_chat_delete', array( __CLASS__, 'delete_conversation' ) );
	}

	/**
	 * Prefixed table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Creates the log table once (flagged by an option so the check is cheap).
	 */
	public static function maybe_create_table() {
		if ( '1' === get_option( self::DBV ) ) {
			return;
		}
		self::create_table();
	}

	/**
	 * dbDelta schema — safe to run repeatedly.
	 */
	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = 'CREATE TABLE ' . self::table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session varchar(64) NOT NULL DEFAULT '',
			role varchar(10) NOT NULL DEFAULT 'visitor',
			message text NULL,
			page varchar(255) NOT NULL DEFAULT '',
			created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY session (session),
			KEY created (created)
		) " . $wpdb->get_charset_collate() . ';';
		dbDelta( $sql );
		update_option( self::DBV, '1', false );
	}

	/**
	 * Built-in bot settings — the admin edits these on the Chat Bot screen.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'  => 1,
			'title'    => 'Ash River Collective',
			'greeting' => 'Hi! I\'m the ARC assistant. Ask me about our services, roles, process or pricing — or type "human" and we\'ll follow up by email.',
			'fallback' => 'I can help with services, roles, process, pricing and timelines. For anything else, leave your email or use the contact form and the team will reply within one business day.',
			'pairs'    => array(
				array(
					'kw' => 'services, service, what do you do, offer',
					'a'  => 'ARC places trained Finance & Accounting talent and Virtual Assistants, and documents your processes into playbooks. Tell us what you need and we scope the role.',
				),
				array(
					'kw' => 'accountant, accounting, bookkeeper, controller, finance, cfo',
					'a'  => 'We place General Accountants, AR/AP Specialists, Senior Accountants, Accounting Managers, Controllers and Fractional Controllers — fluent English, U.S. hours, trained on your systems.',
				),
				array(
					'kw' => 'virtual assistant, va, assistant, admin',
					'a'  => 'ARC Virtual Assistants handle inbox, calendar, data entry, invoicing, CRM updates and reporting — with documented processes behind them, not improvisation.',
				),
				array(
					'kw' => 'price, pricing, cost, rate, how much',
					'a'  => 'Engagements are scoped to the work and priced for the role — dedicated, part-time or project-based. Use the contact form for a same-day quote.',
				),
				array(
					'kw' => 'process, how it works, onboarding, start, timeline',
					'a'  => 'Our model is Diagnose → Install → Execute: we map the work, document the playbook, then place talent that runs it. Most roles start within 7 days.',
				),
				array(
					'kw' => 'contact, email, phone, human, talk, call',
					'a'  => 'You can reach the team through the contact page or leave your work email here — a specialist replies within one business day.',
				),
				array(
					'kw' => 'foundation, nonprofit, training',
					'a'  => 'The Ash River Foundation trains and connects talent in underserved communities — because opportunity shouldn\'t depend on geography.',
				),
				array(
					'kw' => 'hi, hello, hey, hola',
					'a'  => 'Hello! Ask me about roles, pricing, timelines or how ARC works — or type "human" to reach the team.',
				),
			),
		);
	}

	/**
	 * Current settings — option merged over the defaults so new keys always
	 * exist.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = (array) get_option( self::OPTION, array() );
		$out   = array_merge( self::defaults(), $saved );
		if ( empty( $out['pairs'] ) || ! is_array( $out['pairs'] ) ) {
			$out['pairs'] = self::defaults()['pairs'];
		}
		return $out;
	}

	/**
	 * REST namespace routes — public: the widget reads config and posts log
	 * lines for visitors who aren't logged in.
	 */
	public static function rest_routes() {
		register_rest_route(
			'arc-st/v1',
			'/chat/config',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					$s = self::settings();
					return rest_ensure_response(
						array(
							'enabled'  => (int) $s['enabled'],
							'title'    => (string) $s['title'],
							'greeting' => (string) $s['greeting'],
							'fallback' => (string) $s['fallback'],
							'pairs'    => array_values( (array) $s['pairs'] ),
						)
					);
				},
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'arc-st/v1',
			'/chat/log',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_log' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'session' => array( 'required' => true ),
					'role'    => array( 'required' => true ),
					'message' => array( 'required' => true ),
					'page'    => array( 'required' => false ),
				),
			)
		);
	}

	/**
	 * Stores one chat line from the widget.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function rest_log( $req ) {
		global $wpdb;
		self::maybe_create_table();

		$session = sanitize_key( substr( (string) $req->get_param( 'session' ), 0, 64 ) );
		$role    = in_array( $req->get_param( 'role' ), array( 'visitor', 'bot' ), true ) ? $req->get_param( 'role' ) : 'visitor';
		$message = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'message' ) ), 0, 2000 );
		$page    = mb_substr( esc_url_raw( (string) $req->get_param( 'page' ) ), 0, 255 );

		if ( '' === $session || '' === $message ) {
			return rest_ensure_response( array( 'ok' => false ) );
		}

		// Cheap flood control: cap a session at 500 logged lines.
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE session = %s', $session ) );
		if ( $count >= 500 ) {
			return rest_ensure_response( array( 'ok' => false, 'reason' => 'limit' ) );
		}

		$wpdb->insert(
			self::table(),
			array(
				'session' => $session,
				'role'    => $role,
				'message' => $message,
				'page'    => $page,
				'created' => current_time( 'mysql' ),
			)
		);

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * "Chat Bot" submenu under the plugin menu.
	 */
	public static function menu() {
		add_submenu_page(
			Arc_ST_Admin::PAGE_LIBRARY,
			__( 'Chat Bot', 'arc-starter-templates' ),
			__( 'Chat Bot', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Renders settings + Q&A editor + conversation monitor.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied' );
		}
		$settings = self::settings();
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		require ARC_ST_PATH . 'admin/partials/chat.php';
	}

	/**
	 * Latest conversations (one row per session, with its messages).
	 *
	 * @param int $paged Page number.
	 * @param int $per   Sessions per page.
	 * @return array
	 */
	public static function conversations( $paged = 1, $per = 20 ) {
		global $wpdb;
		$sessions = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT session, MAX(created) AS last, COUNT(*) AS msg_count, MAX(page) AS page
				FROM ' . self::table() . ' GROUP BY session ORDER BY last DESC LIMIT %d OFFSET %d',
				$per,
				max( 0, (int) $paged - 1 ) * $per
			),
			ARRAY_A
		);
		foreach ( $sessions as &$s ) {
			$s['messages'] = (array) $wpdb->get_results(
				$wpdb->prepare(
					'SELECT role, message, created FROM ' . self::table() . ' WHERE session = %s ORDER BY id ASC',
					$s['session']
				),
				ARRAY_A
			);
		}
		return $sessions;
	}

	/**
	 * Distinct session count (for pagination).
	 *
	 * @return int
	 */
	public static function conversation_count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT session) FROM ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Saves the settings form (title, greeting, fallback, Q&A pairs).
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_save' ) ) {
			wp_die( 'Denied' );
		}
		$pairs = array();
		$kws   = isset( $_POST['chat_kw'] ) ? (array) wp_unslash( $_POST['chat_kw'] ) : array();
		$ans   = isset( $_POST['chat_a'] ) ? (array) wp_unslash( $_POST['chat_a'] ) : array();
		foreach ( $kws as $i => $kw ) {
			$kw = sanitize_text_field( (string) $kw );
			$a  = isset( $ans[ $i ] ) ? sanitize_textarea_field( (string) $ans[ $i ] ) : '';
			if ( '' !== $kw && '' !== $a ) {
				$pairs[] = array( 'kw' => $kw, 'a' => $a );
			}
		}
		update_option(
			self::OPTION,
			array(
				'enabled'  => isset( $_POST['chat_enabled'] ) ? 1 : 0,
				'title'    => sanitize_text_field( wp_unslash( isset( $_POST['chat_title'] ) ? $_POST['chat_title'] : '' ) ),
				'greeting' => sanitize_textarea_field( wp_unslash( isset( $_POST['chat_greeting'] ) ? $_POST['chat_greeting'] : '' ) ),
				'fallback' => sanitize_textarea_field( wp_unslash( isset( $_POST['chat_fallback'] ) ? $_POST['chat_fallback'] : '' ) ),
				'pairs'    => $pairs,
			),
			false
		);
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_saved=1' ) );
		exit;
	}

	/**
	 * Deletes the whole log.
	 */
	public static function clear() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_clear' ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_cleared=1' ) );
		exit;
	}

	/**
	 * Deletes one conversation (all lines of a session).
	 */
	public static function delete_conversation() {
		$session = isset( $_GET['session'] ) ? sanitize_key( wp_unslash( $_GET['session'] ) ) : '';
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_delete_' . $session ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'session' => $session ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_deleted=1' ) );
		exit;
	}
}
