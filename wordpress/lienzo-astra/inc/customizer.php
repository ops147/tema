<?php
/**
 * Design Options — an Astra-inspired Customizer panel.
 *
 * Adds native WordPress Customizer controls for colors, typography,
 * container width and header/footer layout, then prints the matching
 * CSS custom properties on the front end. This is original code written
 * for Lienzo Astra; it does not reuse any Astra files.
 *
 * The implementation is split across inc/customizer/ by concern:
 * data.php (font choices, palettes, defaults, option reader),
 * sanitizers.php (setting whitelists), controls.php (sections, settings,
 * controls and live preview), fonts.php (Google Fonts), css.php (front-end
 * custom properties + chrome), frontend.php (header breakpoint and
 * scroll-to-top) and editor.php (block-editor palette sync and assets).
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once LIENZOASTRA_DIR . '/inc/customizer/data.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/sanitizers.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/controls.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/fonts.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/css.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/frontend.php';
require_once LIENZOASTRA_DIR . '/inc/customizer/editor.php';
