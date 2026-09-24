<?php
/**
 * Front-end styles.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_asset_version' ) ) {
	/**
	 * Cache-bust asset URLs with the file's modification time so browser
	 * caches pick up changes without manual version bumps.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 *
	 * @return int|string File mtime, or the theme version as a fallback.
	 */
	function lienzo_asset_version( $relative_path ) {
		$path = LIENZOASTRA_DIR . '/' . ltrim( $relative_path, '/' );

		return file_exists( $path ) ? (int) filemtime( $path ) : LIENZOASTRA_VERSION;
	}
}

if ( ! function_exists( 'lienzo_scripts_styles' ) ) {
	/**
	 * Enqueue the theme stylesheets.
	 *
	 * Each sheet can be dropped with its own filter (or from Appearance > Lienzo),
	 * which is handy when Elementor's Site Settings should own the styling.
	 *
	 * @return void
	 */
	function lienzo_scripts_styles() {
		if ( lienzo_is_arc_portal_page() ) {
			return; // ARC Careers portals bypass the theme — never restyle them.
		}

		if ( lienzo_is_arc_template_page() ) {
			// ARC starter pages ship their own Tailwind design system — the
			// reset/theme sheets would fight it (boxed .site-main, underlined
			// links, #c36 buttons, re-bordered inputs). Keep only the chrome
			// sheet: the theme header/footer still renders around the page.
			$styles = lienzo_display_header_footer()
				? [ 'lienzo-header-footer' => 'header-footer.css' ]
				: [];
		} else {
			$styles = [];

			if ( apply_filters( 'lienzo_enqueue_reset', true ) ) {
				$styles['lienzo-reset'] = 'reset.css';
			}

			if ( apply_filters( 'lienzo_enqueue_theme_style', true ) ) {
				$styles['lienzo-theme'] = 'theme.css';
			}

			if ( lienzo_display_header_footer() ) {
				$styles['lienzo-header-footer'] = 'header-footer.css';
			}
		}

		foreach ( $styles as $handle => $file ) {
			wp_enqueue_style( $handle, LIENZOASTRA_URI . '/assets/css/' . $file, [], lienzo_asset_version( 'assets/css/' . $file ) );
			// Loads the matching *-rtl.css file on right-to-left sites.
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'lienzo_scripts_styles' );
