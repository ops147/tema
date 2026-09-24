<?php
/**
 * Lienzo Astra — theme bootstrap.
 *
 * Unifies Lienzo (lightweight, Elementor-ready base — derived from Hello
 * Elementor 3.5.1, Elementor Team, GPL-3.0-or-later) with a native,
 * Astra-inspired Customizer layer (colors, typography, container width,
 * header & footer layout). No Astra code is included; the design-options
 * UX is reimplemented on top of Lienzo's own architecture so both
 * Elementor and the WordPress block editor keep working normally.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'LIENZOASTRA_VERSION', '1.2.3' );
define( 'LIENZOASTRA_DIR', get_template_directory() );
define( 'LIENZOASTRA_URI', get_template_directory_uri() );
define( 'LIENZOASTRA_OPTION', 'lienzoastra_settings' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

/**
 * Whether the current request is an ARC Careers standalone portal page.
 * Those pages bypass the theme entirely, so the theme must never enqueue
 * or print assets/markup there. Detection is by convention, not
 * dependency — false when the plugin is absent.
 *
 * @return bool
 */
function lienzo_is_arc_portal_page() {
	return function_exists( 'arc_careers_current_portal' ) && '' !== arc_careers_current_portal();
}

require_once LIENZOASTRA_DIR . '/inc/setup.php';
require_once LIENZOASTRA_DIR . '/inc/arc-templates.php';
require_once LIENZOASTRA_DIR . '/inc/arc-copy.php';
require_once LIENZOASTRA_DIR . '/inc/assets.php';
require_once LIENZOASTRA_DIR . '/inc/settings-page.php';
require_once LIENZOASTRA_DIR . '/inc/customizer.php';
require_once LIENZOASTRA_DIR . '/inc/elementor.php';
require_once LIENZOASTRA_DIR . '/inc/elementor/widgets.php';
require_once LIENZOASTRA_DIR . '/inc/seo.php';
require_once LIENZOASTRA_DIR . '/inc/optimization.php';
require_once LIENZOASTRA_DIR . '/inc/features.php';
require_once LIENZOASTRA_DIR . '/inc/patterns.php';
require_once LIENZOASTRA_DIR . '/inc/blog.php';
require_once LIENZOASTRA_DIR . '/inc/woocommerce.php';
require_once LIENZOASTRA_DIR . '/inc/starter-sites.php';
require_once LIENZOASTRA_DIR . '/inc/admin-notices.php';
require_once LIENZOASTRA_DIR . '/inc/cleanup.php';
