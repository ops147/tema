<?php
/**
 * Plugin Name:       ARC Starter Templates
 * Plugin URI:        https://ashrivercollective.com
 * Description:       Starter-templates library with 21 Karibase-styled sites — one card per demo, step-by-step wizard, Elementor-editable or Gutenberg block pages, Media Library images, nav menus, front page and stored form entries. Also registers every page as a block pattern.
 * Version:           1.7.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ash River Collective
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       arc-starter-templates
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

define( 'ARC_ST_VERSION', '1.7.0' );
define( 'ARC_ST_FILE', __FILE__ );
define( 'ARC_ST_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARC_ST_URL', plugin_dir_url( __FILE__ ) );

require_once ARC_ST_PATH . 'includes/class-arc-st-remote.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-templates.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-state.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-theme.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-media.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-elementor.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-blocks.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-importer.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-contact.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-entries.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-chat.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-admin.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-patterns.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-assets.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-cli.php';
require_once ARC_ST_PATH . 'includes/class-arc-st-plugin.php';

Arc_ST_Plugin::instance();
