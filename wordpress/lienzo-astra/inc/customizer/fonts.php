<?php
/**
 * Customizer — Google Fonts URL, enqueue, resource hints and font stacks.
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
 * Google Fonts stylesheet URL for any non-system font in use, or an empty
 * string when both font choices are the system stack.
 *
 * @return string
 */
function lienzoastra_google_fonts_url() {
	$body    = lienzoastra_get_option( 'font_family_body' );
	$heading = lienzoastra_get_option( 'font_family_heading' );

	$families = array_unique( array_filter( [ $body, $heading ], function ( $font ) {
		return 'system' !== $font;
	} ) );

	if ( empty( $families ) ) {
		return '';
	}

	$query = [];
	foreach ( $families as $family ) {
		$query[] = str_replace( ' ', '+', $family ) . ':wght@400;600;700';
	}

	return add_query_arg(
		[
			'family'  => implode( '&family=', $query ),
			'display' => 'swap',
		],
		'https://fonts.googleapis.com/css2'
	);
}

/**
 * Enqueue a Google Fonts stylesheet for any non-system font in use.
 *
 * @return void
 */
function lienzoastra_enqueue_google_fonts() {
	if ( lienzo_is_arc_portal_page() ) {
		return; // ARC Careers portals bypass the theme — never restyle them.
	}

	$src = lienzoastra_google_fonts_url();

	if ( '' === $src ) {
		return;
	}

	wp_enqueue_style( 'lienzoastra-google-fonts', $src, [], null );
}
add_action( 'wp_enqueue_scripts', 'lienzoastra_enqueue_google_fonts' );

/**
 * Warm up the connection to the Google Fonts servers so the font
 * stylesheet (and then the font files) arrives sooner.
 *
 * @param array  $urls          URLs hinted for the given relation.
 * @param string $relation_type Resource hint type (preconnect, dns-prefetch…).
 *
 * @return array
 */
function lienzoastra_fonts_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type || '' === lienzoastra_google_fonts_url() ) {
		return $urls;
	}

	$urls[] = 'https://fonts.googleapis.com';
	$urls[] = [
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => true,
	];

	return $urls;
}
add_filter( 'wp_resource_hints', 'lienzoastra_fonts_resource_hints', 10, 2 );

/**
 * Build the `font-family` value for a Design Options font choice.
 *
 * @param string $font Chosen font key.
 *
 * @return string
 */
function lienzoastra_font_stack( $font ) {
	if ( 'system' === $font ) {
		return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
	}

	return '"' . $font . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
}
