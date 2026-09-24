<?php
/**
 * Elementor integration.
 *
 * - Registers every Theme Builder location (header, footer, single, archive…).
 * - Adds "Lienzo Header" / "Lienzo Footer" tabs to Elementor > Site Settings.
 * - Hides the page title when a document has "Hide Title" enabled.
 * - Live-updates the header/footer preview inside the Elementor editor.
 *
 * Nothing here runs unless Elementor is active.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_dynamic_header_footer' ) ) {
	/**
	 * Whether the header/footer should be driven by Elementor's Site Settings
	 * (dynamic templates) instead of the plain static ones.
	 *
	 * @return bool
	 */
	function lienzo_dynamic_header_footer() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}

		return (bool) apply_filters( 'lienzo_dynamic_header_footer', true );
	}
}

if ( ! function_exists( 'lienzo_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Theme Builder locations.
	 *
	 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager Locations manager.
	 *
	 * @return void
	 */
	function lienzo_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'lienzo_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'lienzo_register_elementor_locations' );

if ( ! function_exists( 'lienzo_check_hide_title' ) ) {
	/**
	 * Hide the page title when the Elementor document asks for it.
	 *
	 * @param bool $val Default value.
	 *
	 * @return bool
	 */
	function lienzo_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = \Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}

		return $val;
	}
}
add_filter( 'lienzo_page_title', 'lienzo_check_hide_title' );

/**
 * Register the Site Settings tabs.
 *
 * @return void
 */
function lienzo_register_kit_tabs() {
	if ( ! lienzo_dynamic_header_footer() ) {
		return;
	}

	require_once LIENZOASTRA_DIR . '/inc/elementor/settings-header.php';
	require_once LIENZOASTRA_DIR . '/inc/elementor/settings-footer.php';

	add_action(
		'elementor/kit/register_tabs',
		function ( \Elementor\Core\Kits\Documents\Kit $kit ) {
			if ( ! lienzo_display_header_footer() ) {
				return;
			}

			$kit->register_tab( 'lienzo-settings-header', \Lienzo\Settings\Settings_Header::class );
			$kit->register_tab( 'lienzo-settings-footer', \Lienzo\Settings\Settings_Footer::class );
		},
		1,
		40
	);
}
add_action( 'elementor/init', 'lienzo_register_kit_tabs' );

/**
 * Helper: read one setting from the active Elementor kit.
 *
 * @param string $setting_id Kit control ID (e.g. `lienzo_header_logo_display`).
 *
 * @return string|array Same shape Elementor returns internally ('' when unset).
 */
function lienzo_get_kit_setting( $setting_id ) {
	static $kit_settings = null;

	$value = '';

	if ( did_action( 'elementor/loaded' ) ) {
		if ( null === $kit_settings ) {
			$kit          = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
			$kit_settings = $kit ? $kit->get_settings() : [];
		}

		if ( isset( $kit_settings[ $setting_id ] ) ) {
			$value = $kit_settings[ $setting_id ];
		}
	}

	return apply_filters( "lienzo_kit_setting/{$setting_id}", $value );
}

/**
 * Helper: map a switcher setting to a `show` / `hide` CSS class.
 *
 * @param string $setting_id Kit control ID.
 *
 * @return string
 */
function lienzo_show_or_hide( $setting_id ) {
	return ( 'yes' === lienzo_get_kit_setting( $setting_id ) ? 'show' : 'hide' );
}

/**
 * Helper: translate the header settings into CSS classes.
 *
 * @return string
 */
function lienzo_get_header_layout_class() {
	$layout_classes = [];

	$header_layout = lienzo_get_kit_setting( 'lienzo_header_layout' );
	if ( 'inverted' === $header_layout ) {
		$layout_classes[] = 'header-inverted';
	} elseif ( 'stacked' === $header_layout ) {
		$layout_classes[] = 'header-stacked';
	}

	if ( 'full-width' === lienzo_get_kit_setting( 'lienzo_header_width' ) ) {
		$layout_classes[] = 'header-full-width';
	}

	$header_menu_dropdown = lienzo_get_kit_setting( 'lienzo_header_menu_dropdown' );
	if ( 'tablet' === $header_menu_dropdown ) {
		$layout_classes[] = 'menu-dropdown-tablet';
	} elseif ( 'mobile' === $header_menu_dropdown ) {
		$layout_classes[] = 'menu-dropdown-mobile';
	} elseif ( 'none' === $header_menu_dropdown ) {
		$layout_classes[] = 'menu-dropdown-none';
	}

	if ( 'dropdown' === lienzo_get_kit_setting( 'lienzo_header_menu_layout' ) ) {
		$layout_classes[] = 'menu-layout-dropdown';
	}

	return implode( ' ', $layout_classes );
}

/**
 * Helper: translate the footer settings into CSS classes.
 *
 * @return string
 */
function lienzo_get_footer_layout_class() {
	$layout_classes = [];

	$footer_layout = lienzo_get_kit_setting( 'lienzo_footer_layout' );
	if ( 'inverted' === $footer_layout ) {
		$layout_classes[] = 'footer-inverted';
	} elseif ( 'stacked' === $footer_layout ) {
		$layout_classes[] = 'footer-stacked';
	}

	if ( 'full-width' === lienzo_get_kit_setting( 'lienzo_footer_width' ) ) {
		$layout_classes[] = 'footer-full-width';
	}

	if ( lienzo_get_kit_setting( 'lienzo_footer_copyright_display' ) && '' !== lienzo_get_kit_setting( 'lienzo_footer_copyright_text' ) ) {
		$layout_classes[] = 'footer-has-copyright';
	}

	return implode( ' ', $layout_classes );
}

/**
 * Helper: whether the dynamic header has anything to output.
 *
 * @return bool
 */
function lienzo_get_header_display() {
	$is_editor = isset( $_GET['elementor-preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return (
		$is_editor
		|| lienzo_get_kit_setting( 'lienzo_header_logo_display' )
		|| lienzo_get_kit_setting( 'lienzo_header_tagline_display' )
		|| lienzo_get_kit_setting( 'lienzo_header_menu_display' )
		|| ( function_exists( 'lienzoastra_cart_link' ) && '' !== lienzoastra_cart_link() )
	);
}

/**
 * Helper: whether the dynamic footer has anything to output.
 *
 * @return bool
 */
function lienzo_get_footer_display() {
	$is_editor = isset( $_GET['elementor-preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return (
		$is_editor
		|| lienzo_get_kit_setting( 'lienzo_footer_logo_display' )
		|| lienzo_get_kit_setting( 'lienzo_footer_tagline_display' )
		|| lienzo_get_kit_setting( 'lienzo_footer_menu_display' )
		|| lienzo_get_kit_setting( 'lienzo_footer_copyright_display' )
	);
}

/**
 * Editor: live preview of the Site Settings controls.
 */
add_action(
	'elementor/editor/after_enqueue_scripts',
	function () {
		if ( ! lienzo_dynamic_header_footer() ) {
			return;
		}

		wp_enqueue_script(
			'lienzo-editor',
			LIENZOASTRA_URI . '/assets/js/editor.js',
			[ 'jquery', 'elementor-editor' ],
			LIENZOASTRA_VERSION,
			true
		);
	}
);

/**
 * Front end: mobile menu toggle (both the static and the dynamic header
 * render the toggle + dropdown markup) plus the kit styles needed by the
 * dynamic header/footer when Elementor drives them.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( lienzo_is_arc_portal_page() || ! lienzo_display_header_footer() ) {
			return;
		}

		wp_enqueue_script(
			'lienzo-frontend',
			LIENZOASTRA_URI . '/assets/js/frontend.js',
			[],
			LIENZOASTRA_VERSION,
			true
		);

		if ( lienzo_dynamic_header_footer() ) {
			\Elementor\Plugin::$instance->kits_manager->frontend_before_enqueue_styles();
		}
	}
);
