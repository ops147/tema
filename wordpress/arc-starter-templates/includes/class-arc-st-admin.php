<?php
/**
 * Admin UI — solace-extra style "Starter Templates" screen (single local
 * demo card) plus an AJAX-driven import wizard.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * wp-admin screens and AJAX import steps.
 */
final class Arc_ST_Admin {

	const PAGE_LIBRARY = 'arc-starter-templates';
	const PAGE_WIZARD  = 'arc-st-import';
	const NONCE        = 'arc_st_ajax';
	const SLUG         = 'arc-site'; // The single demo/template id.

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_arc_st_preview', array( __CLASS__, 'handle_preview' ) );
		add_action( 'admin_post_arc_st_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_arc_st_remove', array( __CLASS__, 'handle_remove' ) );
		add_action( 'admin_notices', array( __CLASS__, 'activated_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'requirements_notice' ) );
		add_action( 'wp_ajax_arc_st_step_begin', array( __CLASS__, 'ajax_begin' ) );
		add_action( 'wp_ajax_arc_st_step_finish', array( __CLASS__, 'ajax_finish' ) );
		add_action( 'wp_ajax_arc_st_step_reset', array( __CLASS__, 'ajax_reset' ) );
		add_action( 'wp_ajax_arc_st_step_media', array( __CLASS__, 'ajax_media' ) );
		add_action( 'wp_ajax_arc_st_step_page', array( __CLASS__, 'ajax_page' ) );
		add_action( 'wp_ajax_arc_st_step_setup', array( __CLASS__, 'ajax_setup' ) );
	}

	/**
	 * Library page + hidden wizard page.
	 */
	public static function menu() {
		add_menu_page(
			__( 'ARC Starter Templates', 'arc-starter-templates' ),
			__( 'ARC Templates', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE_LIBRARY,
			array( __CLASS__, 'render_library' ),
			'dashicons-welcome-widgets-menus',
			58
		);
		add_submenu_page(
			self::PAGE_LIBRARY,
			__( 'Starter Templates', 'arc-starter-templates' ),
			__( 'Starter Templates', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE_LIBRARY,
			array( __CLASS__, 'render_library' )
		);
		// Hidden wizard screen — reached only via the demo card.
		add_submenu_page(
			'arc-st-hidden',
			__( 'Import Template', 'arc-starter-templates' ),
			__( 'Import', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE_WIZARD,
			array( __CLASS__, 'render_wizard' )
		);
	}

	/**
	 * Card descriptors for every bundled demo (one card per site).
	 *
	 * @return array id => card data.
	 */
	public static function demos() {
		$cards = array();
		foreach ( Arc_ST_Templates::demos() as $id => $meta ) {
			$home = (string) $meta['home'];
			$cards[ $id ] = array(
				'id'          => $id,
				'home'        => $home,
				'title'       => $meta['title'],
				'desc'        => $meta['desc'],
				'categories'  => (array) $meta['categories'],
				'image'       => Arc_ST_Templates::demo_image_url( $id ),
				'pages'       => (array) $meta['pages'],
				'wizard_url'  => add_query_arg(
					array( 'demo' => $id, 'autorun' => '1' ),
					admin_url( 'admin.php?page=' . self::PAGE_WIZARD )
				),
				'preview_url' => wp_nonce_url(
					admin_url( 'admin-post.php?action=arc_st_preview&slug=' . $home ),
					'arc_st_preview_' . $home
				),
			);
		}
		return $cards;
	}

	/**
	 * The requested wizard demo id (validated against the manifest).
	 *
	 * @return string
	 */
	private static function current_demo_id() {
		$id = isset( $_GET['demo'] ) ? sanitize_key( wp_unslash( $_GET['demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $id && Arc_ST_Templates::demo( $id ) ) {
			return $id;
		}
		$demos = Arc_ST_Templates::demos();
		return (string) ( array_key_exists( self::SLUG, $demos ) ? self::SLUG : key( $demos ) );
	}

	/**
	 * Loads admin CSS/JS only on this plugin's screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		$page       = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_library = 'toplevel_page_' . self::PAGE_LIBRARY === $hook || self::PAGE_LIBRARY === $page;
		$is_wizard  = in_array(
			$hook,
			array( 'admin_page_' . self::PAGE_WIZARD, 'arc-st-hidden_page_' . self::PAGE_WIZARD ),
			true
		) || self::PAGE_WIZARD === $page;
		if ( ! $is_library && ! $is_wizard ) {
			return;
		}

		wp_enqueue_style( 'arc-st-admin', ARC_ST_URL . 'admin/css/admin.css', array(), ARC_ST_VERSION );

		$localized = array(
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( self::NONCE ),
			'wizard'    => admin_url( 'admin.php?page=' . self::PAGE_WIZARD ),
			'library'   => admin_url( 'admin.php?page=' . self::PAGE_LIBRARY ),
			'site'      => home_url( '/' ),
			'demo'      => self::current_demo_id(),
			'slugs'     => array_keys( Arc_ST_Templates::all() ),
			'names'     => wp_list_pluck( Arc_ST_Templates::all(), 'name' ),
			'state'     => Arc_ST_State::state(),
			'locked'    => Arc_ST_State::locked(),
			'i18n'      => array(
				'start'      => __( 'Start Import', 'arc-starter-templates' ),
				'resume'     => __( 'Resume Import', 'arc-starter-templates' ),
				'retry'      => __( 'Retry', 'arc-starter-templates' ),
				'importing'  => __( 'Importing…', 'arc-starter-templates' ),
				'done'       => __( 'Import complete!', 'arc-starter-templates' ),
				'failed'     => __( 'Import failed', 'arc-starter-templates' ),
				'dryDone'    => __( 'Dry run complete — nothing was written.', 'arc-starter-templates' ),
				'viewSite'   => __( 'View Site', 'arc-starter-templates' ),
				'editHome'   => __( 'Edit Homepage', 'arc-starter-templates' ),
				'back'       => __( 'Back to Templates', 'arc-starter-templates' ),
				'stepMedia'  => __( 'Uploading images to Media Library', 'arc-starter-templates' ),
				'stepPages'  => __( 'Creating pages', 'arc-starter-templates' ),
				'stepSetup'  => __( 'Configuring site', 'arc-starter-templates' ),
				'stepReset'  => __( 'Removing previously imported pages', 'arc-starter-templates' ),
				'autorun'    => __( 'Starting the full import automatically — all pages, images and site settings.', 'arc-starter-templates' ),
				'confirmDel' => __( 'Delete every page, menu and setting this plugin imported? This cannot be undone.', 'arc-starter-templates' ),
			),
		);

		if ( $is_library && ! $is_wizard ) {
			wp_enqueue_script( 'arc-st-starter', ARC_ST_URL . 'admin/js/starter.js', array(), ARC_ST_VERSION, true );
			wp_localize_script( 'arc-st-starter', 'arcSt', $localized );
			// Same guard as the wizard script: when a security layer blocks
			// direct plugin JS URLs, the library still works (preview/search).
			$starter_file = ARC_ST_PATH . 'admin/js/starter.js';
			if ( is_readable( $starter_file ) ) {
				$starter_code = file_get_contents( $starter_file );
				if ( false !== $starter_code ) {
					wp_add_inline_script( 'arc-st-starter', $starter_code, 'after' );
				}
			}
		} else {
			wp_enqueue_script( 'arc-st-import', ARC_ST_URL . 'admin/js/import.js', array(), ARC_ST_VERSION, true );
			wp_localize_script( 'arc-st-import', 'arcSt', $localized );
			// Some security layers return 403 for direct plugin JavaScript URLs.
			// Keep the normal file for browsers that allow it, but also print a
			// guarded inline fallback so the wizard never renders as a dead button.
			$import_file = ARC_ST_PATH . 'admin/js/import.js';
			if ( is_readable( $import_file ) ) {
				$import_code = file_get_contents( $import_file );
				if ( false !== $import_code ) {
					wp_add_inline_script( 'arc-st-import', $import_code, 'after' );
				}
			}
		}
	}

	/**
	 * Renders the starter-templates library screen.
	 */
	public static function render_library() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied' );
		}
		$demos     = self::demos();
		$elementor = Arc_ST_Elementor::available();
		$imported  = Arc_ST_Importer::page_map();
		$report    = Arc_ST_State::report();
		$settings  = array(
			'contact_email' => get_option( 'arc_st_contact_email', get_option( 'admin_email' ) ),
			'consent_text'  => get_option( 'arc_st_consent_text', '' ),
			'remote_base'   => Arc_ST_Remote::base(),
			'token_set'     => '' !== Arc_ST_Remote::token(),
		);
		$pages     = Arc_ST_Templates::all();
		require ARC_ST_PATH . 'admin/partials/library.php';
	}

	/**
	 * Renders the import wizard screen.
	 */
	public static function render_wizard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied' );
		}
		$demo_id   = self::current_demo_id();
		$demo      = self::demos();
		$demo      = isset( $demo[ $demo_id ] ) ? $demo[ $demo_id ] : reset( $demo );
		$elementor = Arc_ST_Elementor::available();
		$state     = Arc_ST_State::state();
		$all       = Arc_ST_Templates::all();
		$pages     = array_intersect_key( $all, array_flip( (array) $demo['pages'] ) );
		require ARC_ST_PATH . 'admin/partials/wizard.php';
	}

	/**
	 * Shared AJAX guard.
	 */
	private static function guard() {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'error' => 'Invalid nonce' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => 'Unauthorized' ), 401 );
		}
	}

	/**
	 * Whether the current request is a dry run (report only, no writes).
	 *
	 * @return bool
	 */
	private static function is_dry() {
		return isset( $_POST['dry'] ) && '1' === $_POST['dry']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Step 0: acquire the import lock and start a fresh state + report.
	 */
	public static function ajax_begin() {
		self::guard();
		if ( ! Arc_ST_State::lock_acquire() ) {
			wp_send_json_error(
				array( 'error' => __( 'Another import is already running. Try again in a few minutes.', 'arc-starter-templates' ) ),
				409
			);
		}

		// Fresh (non-resume) run: drop the remote download cache so the
		// manifest and documents below come from the repo's current state.
		if ( Arc_ST_Remote::enabled() && ! ( isset( $_POST['resume'] ) && '1' === $_POST['resume'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			Arc_ST_Remote::flush();
		}

		$demo = isset( $_POST['demo'] ) ? sanitize_key( wp_unslash( $_POST['demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! Arc_ST_Templates::demo( $demo ) ) {
			$demo = '';
		}

		$slugs = isset( $_POST['pages'] ) ? (array) wp_unslash( $_POST['pages'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$slugs = array_values( array_filter( array_map( 'sanitize_key', $slugs ), array( 'Arc_ST_Templates', 'get' ) ) );
		if ( '' !== $demo ) {
			// Never let a request pull pages from a different demo.
			$slugs = array_values( array_intersect( $slugs, Arc_ST_Templates::demo_pages( $demo ) ) );
		}
		if ( ! $slugs ) {
			$slugs = '' !== $demo ? Arc_ST_Templates::demo_pages( $demo ) : array_keys( Arc_ST_Templates::all() );
		}

		$resume = isset( $_POST['resume'] ) && '1' === $_POST['resume']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $resume ) {
			// Resume: keep completed work, re-open the run.
			Arc_ST_State::patch( array( 'status' => 'running', 'step' => 'begin' ) );
			$state = Arc_ST_State::state();
		} else {
			$state = Arc_ST_State::state_blank();
			$state['status']    = 'running';
			$state['step']      = 'begin';
			$state['demo']      = $demo;
			$state['dry']       = self::is_dry();
			$state['set_front'] = ! isset( $_POST['set_front'] ) || '1' === $_POST['set_front']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$state['started']   = time();
			$state['updated']   = time();
			update_option( Arc_ST_State::STATE, $state, false );

			$mode = self::is_dry() ? 'dry' : ( Arc_ST_Elementor::available() ? 'elementor' : 'classic' );
			Arc_ST_State::report_start( $mode );
		}

		// Create the pages up front (pass 1): without this every ajax_page fill
		// runs before the later pages exist, so cross-template links resolve to
		// dead "#" placeholders in the imported content.
		if ( ! self::is_dry() && empty( $state['dry'] ) ) {
			Arc_ST_Importer::prepare( $slugs );
		}

		wp_send_json_success(
			array(
				'slugs' => $slugs,
				'state' => $state,
			)
		);
	}

	/**
	 * Final step: release the lock and close state + report.
	 */
	public static function ajax_finish() {
		self::guard();
		$status = isset( $_POST['status'] ) && 'failed' === $_POST['status'] ? 'failed' : 'done'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		Arc_ST_State::lock_release();
		Arc_ST_State::patch(
			array(
				'status' => 'done' === $status ? 'done' : 'failed',
				'step'   => 'finish',
			)
		);
		Arc_ST_State::report_patch( array( 'status' => $status ) );
		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * Step: delete previously imported ARC pages (optional).
	 */
	public static function ajax_reset() {
		self::guard();
		Arc_ST_State::lock_refresh();
		$state = Arc_ST_State::state();
		$demo  = isset( $_POST['demo'] ) ? sanitize_key( wp_unslash( $_POST['demo'] ) ) : (string) $state['demo']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( self::is_dry() ) {
			$meta    = Arc_ST_Templates::demo( $demo );
			$targets = $meta ? array_intersect_key( Arc_ST_Importer::page_map(), array_flip( (array) $meta['pages'] ) ) : Arc_ST_Importer::page_map();
			Arc_ST_State::patch( array( 'reset_done' => true, 'step' => 'reset' ) );
			wp_send_json_success( array( 'deleted' => count( $targets ), 'dry' => true ) );
		}
		$deleted = Arc_ST_Importer::reset( $demo );
		Arc_ST_State::patch( array( 'reset_done' => true, 'step' => 'reset' ) );
		Arc_ST_State::report_add( 'setup', sprintf( 'Reset: %d previous pages removed.', $deleted ) );
		wp_send_json_success( array( 'deleted' => $deleted ) );
	}

	/**
	 * Step: upload every bundled image to the Media Library.
	 */
	public static function ajax_media() {
		self::guard();
		Arc_ST_State::lock_refresh();
		$slugs = isset( $_POST['pages'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['pages'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$state = Arc_ST_State::state();
		$demo  = isset( $_POST['demo'] ) ? sanitize_key( wp_unslash( $_POST['demo'] ) ) : (string) $state['demo']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $slugs ) {
			$slugs = Arc_ST_Templates::demo( $demo ) ? Arc_ST_Templates::demo_pages( $demo ) : array_keys( Arc_ST_Templates::all() );
		}

		if ( self::is_dry() ) {
			$files = array();
			foreach ( $slugs as $slug ) {
				$files = array_merge( $files, Arc_ST_Templates::image_files( $slug ) );
			}
			Arc_ST_State::patch( array( 'media_done' => true, 'step' => 'media' ) );
			wp_send_json_success( array( 'images' => count( array_unique( $files ) ), 'errors' => array(), 'dry' => true ) );
		}

		$map    = array();
		$errors = array();
		foreach ( $slugs as $slug ) {
			$map   += Arc_ST_Media::import_for_template( $slug );
			$errors = array_merge( $errors, Arc_ST_Media::last_errors() );
		}

		Arc_ST_State::patch(
			array(
				'media_done' => true,
				'step'       => 'media',
				'errors'     => array_values( $errors ),
			)
		);
		Arc_ST_State::report_patch(
			array(
				'images' => array(
					'imported' => count( $map ),
					'errors'   => $errors,
				),
			)
		);
		wp_send_json_success( array( 'images' => count( $map ), 'errors' => $errors ) );
	}

	/**
	 * Step: import one page (called once per template slug).
	 */
	public static function ajax_page() {
		self::guard();
		Arc_ST_State::lock_refresh();
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$meta = Arc_ST_Templates::get( $slug );
		if ( ! $meta ) {
			wp_send_json_error( array( 'error' => 'Unknown template' ), 400 );
		}

		if ( self::is_dry() ) {
			$state = Arc_ST_State::state();
			$state['pages_done'][] = $slug;
			Arc_ST_State::patch( array( 'pages_done' => $state['pages_done'], 'step' => 'page:' . $slug ) );
			wp_send_json_success( array( 'page_id' => 0, 'title' => $meta['name'], 'dry' => true ) );
		}

		$result = Arc_ST_Importer::import( $slug );
		if ( is_wp_error( $result ) ) {
			$state = Arc_ST_State::state();
			$state['errors'][] = $meta['name'] . ': ' . $result->get_error_message();
			Arc_ST_State::patch( array( 'errors' => $state['errors'] ) );
			Arc_ST_State::report_add( 'errors', $meta['name'] . ': ' . $result->get_error_message() );
			wp_send_json_error( array( 'error' => $result->get_error_message() ) );
		}

		$state = Arc_ST_State::state();
		$state['pages_done'][] = $slug;
		Arc_ST_State::patch(
			array(
				'pages_done' => $state['pages_done'],
				'step'       => 'page:' . $slug,
			)
		);
		Arc_ST_State::report_add(
			'pages',
			array(
				'slug' => $slug,
				'name' => $meta['name'],
				'id'   => (int) $result,
			)
		);

		wp_send_json_success(
			array(
				'page_id' => (int) $result,
				'link'    => get_permalink( $result ),
				'edit'    => get_edit_post_link( $result, 'raw' ),
			)
		);
	}

	/**
	 * Step: front page, nav menus and Elementor flags + kit brand colors.
	 */
	public static function ajax_setup() {
		self::guard();
		Arc_ST_State::lock_refresh();

		$state     = Arc_ST_State::state();
		$set_front = isset( $_POST['set_front'] ) ? '1' === $_POST['set_front'] : (bool) $state['set_front']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$demo      = isset( $_POST['demo'] ) ? sanitize_key( wp_unslash( $_POST['demo'] ) ) : (string) $state['demo']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$details   = Arc_ST_Importer::setup_site( $set_front, self::is_dry(), $demo );

		Arc_ST_State::patch( array( 'setup_done' => true, 'step' => 'setup' ) );
		Arc_ST_State::report_patch( array( 'setup' => $details ) );

		wp_send_json_success( $details );
	}

	/**
	 * Saves plugin settings from the library screen (contact recipient,
	 * consent text).
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_settings' ) ) {
			wp_die( 'Denied' );
		}
		// Each settings block posts only its own fields — absent keys are
		// left untouched.
		if ( isset( $_POST['arc_st_contact_email'] ) ) {
			$email = sanitize_email( wp_unslash( $_POST['arc_st_contact_email'] ) );
			update_option( 'arc_st_contact_email', $email ? $email : get_option( 'admin_email' ), false );
		}
		if ( isset( $_POST['arc_st_consent_text'] ) ) {
			$consent = sanitize_text_field( wp_unslash( $_POST['arc_st_consent_text'] ) );
			update_option( 'arc_st_consent_text', $consent, false );
		}

		// Templates repository — only touched when the field ships with the
		// form, so the contact-only form can't wipe it.
		if ( isset( $_POST['arc_st_remote_base'] ) ) {
			$base = esc_url_raw( trim( (string) wp_unslash( $_POST['arc_st_remote_base'] ) ) );
			$base = '' !== $base ? untrailingslashit( $base ) : '';
			if ( $base !== (string) get_option( Arc_ST_Remote::OPTION, '' ) ) {
				update_option( Arc_ST_Remote::OPTION, $base, false );
				Arc_ST_Remote::flush(); // New source — stale cached files are useless.
			}
		}

		// Private-repo token: the field ships empty on purpose — a non-empty
		// submission replaces it, the checkbox clears it. Never echoed back.
		if ( ! empty( $_POST['arc_st_remote_token_clear'] ) ) {
			delete_option( Arc_ST_Remote::TOKEN_OPTION );
		} elseif ( isset( $_POST['arc_st_remote_token'] ) ) {
			$token = trim( (string) wp_unslash( $_POST['arc_st_remote_token'] ) );
			if ( '' !== $token ) {
				update_option( Arc_ST_Remote::TOKEN_OPTION, $token, false );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_LIBRARY . '&arc_saved=1' ) );
		exit;
	}

	/**
	 * "Remove imported site" — deletes imported pages + ARC menus and clears
	 * the run state/report. Media files are kept (they may be used elsewhere).
	 */
	public static function handle_remove() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_remove' ) ) {
			wp_die( 'Denied' );
		}
		Arc_ST_Importer::reset();
		delete_option( Arc_ST_State::STATE );
		delete_option( Arc_ST_State::REPORT );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_LIBRARY . '&arc_removed=1' ) );
		exit;
	}

	/**
	 * Environment check — warns when the server can't run parts of the
	 * plugin instead of degrading silently.
	 */
	public static function requirements_notice() {
		$missing = array();
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$missing[] = 'PHP 7.4+';
		}
		if ( ! class_exists( 'DOMDocument' ) ) {
			$missing[] = 'PHP ext-dom (needed for Elementor/block conversion)';
		}
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || ! wp_is_writable( $uploads['basedir'] ) ) {
			$missing[] = 'writable uploads directory';
		}
		if ( Arc_ST_Remote::enabled() && '' === Arc_ST_Remote::fetch( 'templates/manifest.json' ) ) {
			$missing[] = 'reachable templates repository (check the remote URL)';
		}
		if ( ! $missing ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'ARC Starter Templates:', 'arc-starter-templates' ),
			esc_html(
				sprintf(
					/* translators: %s: comma-separated missing requirements. */
					__( 'missing requirements — %s.', 'arc-starter-templates' ),
					implode( ', ', $missing )
				)
			)
		);
	}

	/**
	 * One-time pointer to the library after activation.
	 */
	public static function activated_notice() {
		if ( ! get_transient( 'arc_st_activated' ) ) {
			return;
		}
		delete_transient( 'arc_st_activated' );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'ARC Starter Templates is ready.', 'arc-starter-templates' ),
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_LIBRARY ) ),
			esc_html__( 'Open the template library →', 'arc-starter-templates' )
		);
	}

	/**
	 * Standalone template preview (full document, plugin assets). Internal
	 * href="<slug>.html" links are rewritten to their own preview URLs so the
	 * whole demo is browsable inside the iframe/new tab.
	 */
	public static function handle_preview() {
		$slug = isset( $_GET['slug'] ) ? sanitize_key( wp_unslash( $_GET['slug'] ) ) : '';
		if ( ! current_user_can( 'manage_options' )
			|| ! check_admin_referer( 'arc_st_preview_' . $slug ) ) {
			wp_die( 'Denied' );
		}
		$html = Arc_ST_Templates::full_html( $slug );
		if ( '' === $html ) {
			wp_die( 'Template not found.' );
		}
		$html = Arc_ST_Templates::assets_to_plugin_urls( $html );
		$html = preg_replace_callback(
			'/href="([\w-]+)\.html"/',
			array( __CLASS__, 'preview_link' ),
			$html
		);
		// The contact form is wired to really submit — in the demo it must not.
		$html = str_replace(
			'method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"',
			'onsubmit="return false"',
			$html
		);
		// phpcs:ignore WordPress.Security.EscapeOutput -- intentional raw document preview.
		echo $html;
		exit;
	}

	/**
	 * Maps href="<slug>.html" to the nonced preview URL of that template so
	 * demo pages link to each other inside the preview.
	 *
	 * @param array $m Regex match (full match, slug).
	 * @return string Replacement href attribute.
	 */
	private static function preview_link( $m ) {
		$target = sanitize_key( $m[1] );
		if ( ! Arc_ST_Templates::get( $target ) ) {
			return 'href="#"';
		}
		return 'href="' . esc_url(
			wp_nonce_url(
				admin_url( 'admin-post.php?action=arc_st_preview&slug=' . $target ),
				'arc_st_preview_' . $target
			)
		) . '"';
	}
}
