<?php
/**
 * A single, dismissible admin notice recommending Elementor when it is not
 * yet installed/active. Lienzo works without it (plain templates), but it
 * is built for it.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_maybe_show_elementor_notice' ) ) {
	/**
	 * Print the notice on wp-admin screens when relevant.
	 *
	 * @return void
	 */
	function lienzo_maybe_show_elementor_notice() {
		if ( did_action( 'elementor/loaded' ) ) {
			return;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), 'lienzo_dismissed_elementor_notice', true ) ) {
			return;
		}

		$install_url = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ),
			'install-plugin_elementor'
		);
		?>
		<div class="notice notice-info is-dismissible lienzo-elementor-notice">
			<p>
				<?php
				printf(
					/* translators: 1: theme name, 2: opening link tag, 3: closing link tag. */
					esc_html__( '%1$s is built to work with the Elementor page builder. %2$sInstall & activate Elementor%3$s to unlock the Theme Builder locations and the Lienzo Header/Footer settings.', 'lienzo-astra' ),
					'<strong>Lienzo</strong>',
					'<a href="' . esc_url( $install_url ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var notice = document.querySelector( '.lienzo-elementor-notice' );
			if ( ! notice ) {
				return;
			}
			notice.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( '.notice-dismiss' ) ) {
					fetch( ajaxurl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: 'action=lienzo_dismiss_elementor_notice&_wpnonce=<?php echo esc_js( wp_create_nonce( 'lienzo_dismiss_elementor_notice' ) ); ?>',
					} );
				}
			} );
		} );
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'lienzo_maybe_show_elementor_notice' );

if ( ! function_exists( 'lienzo_dismiss_elementor_notice' ) ) {
	/**
	 * AJAX handler: remember the notice was dismissed for this user.
	 *
	 * @return void
	 */
	function lienzo_dismiss_elementor_notice() {
		check_ajax_referer( 'lienzo_dismiss_elementor_notice' );
		update_user_meta( get_current_user_id(), 'lienzo_dismissed_elementor_notice', 1 );
		wp_die();
	}
}
add_action( 'wp_ajax_lienzo_dismiss_elementor_notice', 'lienzo_dismiss_elementor_notice' );

if ( ! function_exists( 'lienzo_maybe_show_page_clones_notice' ) ) {
	/**
	 * Recommend activating a starter-sites provider when none is active.
	 * ARC Starter Templates is preferred when installed; PAge remains as a
	 * fallback provider.
	 *
	 * @return void
	 */
	function lienzo_maybe_show_page_clones_notice() {
		if ( function_exists( 'lienzoastra_arc_st_active' ) && lienzoastra_arc_st_active() ) {
			return; // ARC Starter Templates already provides starter sites.
		}

		if ( function_exists( 'lienzoastra_page_clones_active' ) && lienzoastra_page_clones_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), 'lienzo_dismissed_page_clones_notice', true ) ) {
			return;
		}

		// Prefer ARC Starter Templates when it is installed but inactive.
		$arc_file = function_exists( 'lienzoastra_arc_st_plugin_file' )
			? lienzoastra_arc_st_plugin_file()
			: '';

		if ( '' !== $arc_file ) {
			$action_url = wp_nonce_url(
				self_admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $arc_file ) ),
				'activate-plugin_' . $arc_file
			);
			$action_label  = __( 'Activate ARC Starter Templates', 'lienzo-astra' );
			$provider_name = 'ARC Starter Templates';
		} else {
			$plugin_file = function_exists( 'lienzoastra_page_clones_plugin_file' )
				? lienzoastra_page_clones_plugin_file()
				: 'PAge/page.php';
			$installed   = '' !== $plugin_file && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );

			if ( $installed ) {
				$action_url = wp_nonce_url(
					self_admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
					'activate-plugin_' . $plugin_file
				);
				$action_label = __( 'Activate PAge', 'lienzo-astra' );
			} else {
				$action_url   = admin_url( 'plugins.php' );
				$action_label = __( 'Go to Plugins', 'lienzo-astra' );
			}
			$provider_name = 'PAge';
		}
		?>
		<div class="notice notice-info is-dismissible lienzo-page-clones-notice">
			<p>
				<?php
				printf(
					/* translators: 1: opening link tag, 2: closing link tag, 3: theme name, 4: action label, 5: provider plugin name. */
					esc_html__( 'The starter sites for %3$s are provided by the %5$s plugin. %1$s%4$s%2$s to create and manage them.', 'lienzo-astra' ),
					'<a href="' . esc_url( $action_url ) . '">',
					'</a>',
					'<strong>Lienzo Astra</strong>',
					esc_html( $action_label ),
					esc_html( $provider_name )
				);
				?>
			</p>
		</div>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var notice = document.querySelector( '.lienzo-page-clones-notice' );
			if ( ! notice ) {
				return;
			}
			notice.addEventListener( 'click', function ( event ) {
				if ( event.target.closest( '.notice-dismiss' ) ) {
					fetch( ajaxurl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: 'action=lienzo_dismiss_page_clones_notice&_wpnonce=<?php echo esc_js( wp_create_nonce( 'lienzo_dismiss_page_clones_notice' ) ); ?>',
					} );
				}
			} );
		} );
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'lienzo_maybe_show_page_clones_notice' );

if ( ! function_exists( 'lienzo_dismiss_page_clones_notice' ) ) {
	/**
	 * AJAX handler: remember the PAge notice was dismissed for this user.
	 *
	 * @return void
	 */
	function lienzo_dismiss_page_clones_notice() {
		check_ajax_referer( 'lienzo_dismiss_page_clones_notice' );
		update_user_meta( get_current_user_id(), 'lienzo_dismissed_page_clones_notice', 1 );
		wp_die();
	}
}
add_action( 'wp_ajax_lienzo_dismiss_page_clones_notice', 'lienzo_dismiss_page_clones_notice' );
