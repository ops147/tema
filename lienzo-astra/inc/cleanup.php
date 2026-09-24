<?php
/**
 * Removes Lienzo Astra data when the active theme is changed.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lienzoastra_cleanup_after_switch( $new_name, $new_theme, $old_theme ) {
	if ( ! $old_theme instanceof WP_Theme ) {
		return;
	}

	$stylesheet = $old_theme->get_stylesheet();
	$template   = $old_theme->get_template();
	if ( 'lienzo-astra' !== $stylesheet && 'lienzo-astra' !== $template ) {
		return;
	}

	delete_option( LIENZOASTRA_OPTION );
	delete_option( 'theme_mods_' . $stylesheet );

	$custom_css_posts = get_posts( [
		'post_type'      => 'custom_css',
		'post_status'    => 'any',
		'name'           => $stylesheet,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );

	foreach ( $custom_css_posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}
add_action( 'switch_theme', 'lienzoastra_cleanup_after_switch', 10, 3 );
