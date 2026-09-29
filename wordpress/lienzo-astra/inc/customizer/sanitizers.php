<?php
/**
 * Customizer — whitelist sanitizers.
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
 * Whitelist sanitizer for the font-choice selects.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_font_choice( $input ) {
	$choices = array_keys( lienzoastra_font_choices() );

	return in_array( $input, $choices, true ) ? $input : 'system';
}

/**
 * Whitelist sanitizer for the header layout select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_header_layout( $input ) {
	return in_array( $input, [ 'left', 'center' ], true ) ? $input : 'left';
}

/**
 * Whitelist sanitizer for the preset-palette select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_palette( $input ) {
	$palettes = lienzoastra_color_palettes();

	return isset( $palettes[ $input ] ) ? $input : 'default';
}

/**
 * Whitelist sanitizer for the ARC template palette select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_arc_palette( $input ) {
	$choices = array( 'default', 'custom' ) + lienzo_arc_palette_presets();

	return isset( $choices[ $input ] ) ? $input : 'default';
}

/**
 * Keeps the pasted copy markdown as plain text — strips HTML tags (which a
 * copy document shouldn't carry) without mangling markdown characters or
 * entities, since pairing relies on exact baseline matches.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_copy_md( $input ) {
	return preg_replace( '/<\/?[a-z][^>]*>/i', '', (string) $input );
}

/**
 * Whitelist sanitizer for the mobile-menu breakpoint select. The values
 * are the class names the header CSS already understands.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_menu_breakpoint( $input ) {
	$choices = [ 'menu-dropdown-mobile', 'menu-dropdown-tablet', 'menu-dropdown-none', 'menu-layout-dropdown' ];

	return in_array( $input, $choices, true ) ? $input : 'menu-dropdown-tablet';
}

/**
 * Whitelist sanitizer for the menu alignment select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_menu_alignment( $input ) {
	return in_array( $input, [ 'left', 'center', 'right' ], true ) ? $input : 'right';
}

/**
 * Whitelist sanitizer for the menu font-weight select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_font_weight( $input ) {
	$input = (string) $input;

	return in_array( $input, [ '400', '500', '600', '700' ], true ) ? $input : '500';
}

/**
 * Whitelist sanitizer for the menu text-transform select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_text_transform( $input ) {
	return in_array( $input, [ 'none', 'uppercase', 'capitalize' ], true ) ? $input : 'none';
}

/**
 * Clamp the body line-height slider to its control range.
 *
 * @param mixed $input Submitted value.
 *
 * @return float
 */
function lienzoastra_sanitize_line_height( $input ) {
	$value = (float) $input;

	return min( 2.2, max( 1.2, $value ) );
}

/**
 * Whitelist sanitizer for the heading font-weight select. Empty means
 * "inherit", so the stylesheet leaves the weight untouched.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_heading_weight( $input ) {
	$input = (string) $input;

	return in_array( $input, [ '', '300', '400', '500', '600', '700', '800', '900' ], true ) ? $input : '';
}

/**
 * Clamp the heading letter-spacing slider (px, can be negative).
 *
 * @param mixed $input Submitted value.
 *
 * @return float
 */
function lienzoastra_sanitize_letter_spacing( $input ) {
	$value = (float) $input;

	return min( 5, max( -2, $value ) );
}

/**
 * Whitelist sanitizer for the site layout select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_site_layout( $input ) {
	return in_array( $input, [ 'full', 'boxed' ], true ) ? $input : 'full';
}

/**
 * Whitelist sanitizer for the menu hover-style select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_menu_hover( $input ) {
	return in_array( $input, [ 'color', 'underline' ], true ) ? $input : 'color';
}

/**
 * Whitelist sanitizer for the footer alignment select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_footer_align( $input ) {
	return in_array( $input, [ 'left', 'center', 'right' ], true ) ? $input : 'center';
}
