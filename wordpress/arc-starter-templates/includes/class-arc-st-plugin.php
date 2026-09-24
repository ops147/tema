<?php
/**
 * Plugin orchestrator.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin modules together.
 */
final class Arc_ST_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Arc_ST_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance.
	 *
	 * @return Arc_ST_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers module hooks.
	 */
	private function __construct() {
		register_activation_hook( ARC_ST_FILE, array( __CLASS__, 'activate' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'i18n' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ARC_ST_FILE ), array( __CLASS__, 'action_links' ) );

		Arc_ST_Admin::hooks();
		Arc_ST_Patterns::hooks();
		Arc_ST_Theme::hooks();
		Arc_ST_Assets::hooks();
		Arc_ST_Contact::hooks();
		Arc_ST_Entries::hooks();
		Arc_ST_Chat::hooks();
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			Arc_ST_CLI::hooks();
		}
	}

	/**
	 * Loads plugin translations (languages/ directory).
	 */
	public static function i18n() {
		load_plugin_textdomain(
			'arc-starter-templates',
			false,
			dirname( plugin_basename( ARC_ST_FILE ) ) . '/languages'
		);
	}

	/**
	 * Activation: create the form-entries table and flag a pointer to the
	 * library screen (shown once).
	 */
	public static function activate() {
		Arc_ST_Entries::create_table();
		set_transient( 'arc_st_activated', 1, 60 );
	}

	/**
	 * Adds quick links on the Plugins list table.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url    = admin_url( 'admin.php?page=' . Arc_ST_Admin::PAGE_LIBRARY );
		$custom = array(
			'library' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Starter Templates', 'arc-starter-templates' ) . '</a>',
		);
		return array_merge( $custom, $links );
	}
}
