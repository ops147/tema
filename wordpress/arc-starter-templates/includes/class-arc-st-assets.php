<?php
/**
 * Frontend/editor assets — loads the compiled Tailwind build, the Elementor
 * bridge stylesheet, site.js and the Outfit font wherever an ARC template is
 * rendered (imported pages, Elementor Canvas, inserted patterns).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Conditional asset loading.
 */
final class Arc_ST_Assets {

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'editor' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'the_content', array( __CLASS__, 'protect_template_markup' ), 0 );
	}

	/**
	 * Whether the current singular view contains an ARC template.
	 *
	 * @return bool
	 */
	private static function page_has_template() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		if ( get_post_meta( $post->ID, Arc_ST_Importer::META_SLUG, true ) ) {
			return true;
		}
		return false !== strpos( (string) $post->post_content, Arc_ST_Patterns::MARKER );
	}

	/**
	 * Enqueues frontend assets on pages that use an ARC template.
	 */
	public static function frontend() {
		if ( ! self::page_has_template() ) {
			return;
		}
		self::enqueue();
	}

	/**
	 * Loads the design system inside the block editor ONLY when the edited
	 * post already contains an ARC template — never for other content, so
	 * third-party blocks keep their own styling.
	 */
	public static function editor() {
		if ( ! is_admin() ) {
			return; // Frontend handled by self::frontend().
		}
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $post_id ) {
			return;
		}
		$is_arc = (bool) get_post_meta( $post_id, Arc_ST_Importer::META_SLUG, true )
			|| false !== strpos( (string) get_post_field( 'post_content', $post_id ), Arc_ST_Patterns::MARKER );
		if ( $is_arc ) {
			self::enqueue();
			// Editor-only canvas fixes (full-width root group, etc.). Reaching
			// this hook during _wp_get_iframed_editor_assets() puts the sheet
			// inside the editor iframe; otherwise it lands in the editor page.
			wp_enqueue_style(
				'arc-st-editor',
				ARC_ST_URL . 'assets/css/editor.css',
				array( 'arc-st-bridge' ),
				ARC_ST_VERSION
			);
		}
	}

	/**
	 * Shared enqueue for both contexts.
	 */
	private static function enqueue() {
		wp_enqueue_style(
			'arc-st-fonts',
			'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap',
			array(),
			null // phpcs:ignore -- third-party font CDN, no local version.
		);
		wp_enqueue_style(
			'arc-st-tailwind',
			ARC_ST_URL . 'assets/css/tailwind.css',
			array( 'arc-st-fonts' ),
			ARC_ST_VERSION
		);
		wp_enqueue_style(
			'arc-st-bridge',
			ARC_ST_URL . 'assets/css/elementor-bridge.css',
			array( 'arc-st-tailwind' ),
			ARC_ST_VERSION
		);
		wp_enqueue_script(
			'arc-st-site',
			ARC_ST_URL . 'assets/js/site.js',
			array(),
			ARC_ST_VERSION,
			true
		);
		wp_script_add_data( 'arc-st-site', 'strategy', 'defer' );
	}

	/**
	 * Adds the template body classes to imported pages.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( self::page_has_template() ) {
			$classes[] = 'arc-tpl';
			$classes[] = 'font-sans';
			$classes[] = 'text-slate-600';
			$classes[] = 'antialiased';
		}
		return $classes;
	}

	/**
	 * Priority-0 the_content guard. The stored ARC markup is a complete
	 * HTML fragment — WordPress's prose transforms (wpautop, wptexturize,
	 * smilies) would inject <p>/<br> into it and break the design. They are
	 * removed for this render, then restored so any other content filtered
	 * later in the same request keeps normal processing.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function protect_template_markup( $content ) {
		if ( ! self::page_has_template() ) {
			return $content;
		}
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'wptexturize' );
		remove_filter( 'the_content', 'shortcode_unautop' );
		remove_filter( 'the_content', 'convert_smilies', 20 );
		add_filter( 'the_content', array( __CLASS__, 'restore_content_filters' ), PHP_INT_MAX );
		return $content;
	}

	/**
	 * Re-adds the content filters suspended by protect_template_markup().
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function restore_content_filters( $content ) {
		add_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'wptexturize' );
		add_filter( 'the_content', 'shortcode_unautop' );
		add_filter( 'the_content', 'convert_smilies', 20 );
		remove_filter( 'the_content', array( __CLASS__, 'restore_content_filters' ), PHP_INT_MAX );
		return $content;
	}
}
