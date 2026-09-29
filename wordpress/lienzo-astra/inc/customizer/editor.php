<?php
/**
 * Customizer — block-editor palette sync and editor assets.
 *
 * Split out of inc/customizer.php (the Design Options loader) so each
 * concern lives in its own file.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Keep the block editor's global palette in sync with Design Options, so
 * blocks and patterns using the theme colors follow the Customizer the
 * same way Kadence's global palette does.
 *
 * @param WP_Theme_JSON_Data $theme_json Theme JSON data object.
 *
 * @return WP_Theme_JSON_Data
 */
function lienzoastra_sync_block_palette( $theme_json ) {
	if ( ! apply_filters( 'lienzoastra_sync_block_palette', true ) ) {
		return $theme_json;
	}

	$palette = [
		[
			'slug'  => 'primary',
			'color' => lienzoastra_get_option( 'color_primary' ),
			'name'  => __( 'Primary', 'lienzo-astra' ),
		],
		[
			'slug'  => 'secondary',
			'color' => lienzoastra_get_option( 'color_text' ),
			'name'  => __( 'Secondary', 'lienzo-astra' ),
		],
		[
			'slug'  => 'surface',
			'color' => '#f5f5f5',
			'name'  => __( 'Surface', 'lienzo-astra' ),
		],
		[
			'slug'  => 'dark',
			'color' => '#121212',
			'name'  => __( 'Dark', 'lienzo-astra' ),
		],
		[
			'slug'  => 'light',
			'color' => lienzoastra_get_option( 'color_background' ),
			'name'  => __( 'Light', 'lienzo-astra' ),
		],
	];

	// update_with() replaces the whole preset list for this origin — merge
	// back any presets other plugins added (e.g. ARC Starter Templates'
	// demo palette) so the theme never clobbers foreign presets.
	$theme_slugs = wp_list_pluck( $palette, 'slug' );
	$existing    = $theme_json->get_data();
	$current     = isset( $existing['settings']['color']['palette'] ) ? (array) $existing['settings']['color']['palette'] : [];
	if ( isset( $current['theme'] ) && is_array( $current['theme'] ) ) {
		$current = $current['theme'];
	}
	foreach ( $current as $preset ) {
		if ( isset( $preset['slug'], $preset['color'] ) && ! in_array( $preset['slug'], $theme_slugs, true ) ) {
			$palette[] = $preset;
		}
	}

	return $theme_json->update_with(
		[
			'version'  => 3,
			'settings' => [
				'color' => [
					'palette' => $palette,
				],
			],
		]
	);
}
add_filter( 'wp_theme_json_data_theme', 'lienzoastra_sync_block_palette' );

/**
 * Give the block editor the same typography, colors and Google Fonts the
 * front end gets, so writing in Gutenberg feels like editing the site
 * itself (Blocksy-style WYSIWYG parity).
 *
 * @return void
 */
function lienzoastra_block_editor_assets() {
	$fonts_url = lienzoastra_google_fonts_url();
	$handle    = 'lienzoastra-editor';

	if ( '' !== $fonts_url ) {
		wp_enqueue_style( $handle, $fonts_url, [], null );
	} else {
		wp_register_style( $handle, false, [], LIENZOASTRA_VERSION );
		wp_enqueue_style( $handle );
	}

	$arc_font_body    = lienzoastra_get_option( 'font_family_body' );
	$arc_font_heading = lienzoastra_get_option( 'font_family_heading' );

	$css = sprintf(
		'.editor-styles-wrapper { background-color: %1$s; color: %2$s; font-family: %3$s; font-size: %4$dpx; }
		.editor-styles-wrapper a { color: %5$s; }
		.editor-styles-wrapper h1, .editor-styles-wrapper h2, .editor-styles-wrapper h3,
		.editor-styles-wrapper h4, .editor-styles-wrapper h5, .editor-styles-wrapper h6 { font-family: %6$s; }',
		esc_html( lienzoastra_get_option( 'color_background' ) ),
		esc_html( lienzoastra_get_option( 'color_text' ) ),
		lienzoastra_font_stack( $arc_font_body ), // Whitelisted font choice — esc_html would break quotes.
		(int) lienzoastra_get_option( 'font_size_base' ),
		esc_html( lienzoastra_get_option( 'color_link' ) ),
		lienzoastra_font_stack( $arc_font_heading )
	);

	// Same bridge as the front end: imported ARC templates set --font-sans /
	// --font-display in their markup, so a Customizer font only reaches the
	// template copy if the tokens are redefined on the wrapper.
	if ( 'system' !== $arc_font_body || 'system' !== $arc_font_heading ) {
		$bridge = '.editor-styles-wrapper .arc-tpl {';
		if ( 'system' !== $arc_font_body ) {
			$bridge .= ' --font-sans: ' . lienzoastra_font_stack( $arc_font_body ) . ';';
		}
		if ( 'system' !== $arc_font_heading ) {
			$bridge .= ' --font-display: ' . lienzoastra_font_stack( $arc_font_heading ) . ';';
		}
		$css .= $bridge . ' }';
	}

	wp_add_inline_style( $handle, $css );
}
add_action( 'enqueue_block_editor_assets', 'lienzoastra_block_editor_assets' );
