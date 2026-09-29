<?php
/**
 * Customizer — header breakpoint + scroll-to-top glue.
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
 * Apply the Customizer's mobile-menu breakpoint to the static header by
 * answering the class filter the header template exposes.
 *
 * @param string $class Default breakpoint class.
 *
 * @return string
 */
function lienzoastra_static_header_breakpoint( $class ) {
	$breakpoint = lienzoastra_get_option( 'header_menu_breakpoint' );

	return '' !== $breakpoint ? $breakpoint : $class;
}
add_filter( 'lienzo_static_header_menu_dropdown', 'lienzoastra_static_header_breakpoint' );

/**
 * Print the floating scroll-to-top button when the option is enabled.
 *
 * @return void
 */
function lienzoastra_scroll_to_top_button() {
	if ( ! lienzoastra_get_option( 'scroll_to_top' ) || lienzo_is_arc_portal_page() ) {
		return;
	}
	?>
	<button type="button" class="lienzoastra-scroll-top" aria-label="<?php echo esc_attr__( 'Scroll to top', 'lienzo-astra' ); ?>">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
	</button>
	<?php
}
add_action( 'wp_footer', 'lienzoastra_scroll_to_top_button' );
