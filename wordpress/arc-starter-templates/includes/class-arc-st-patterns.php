<?php
/**
 * Block patterns — registers every template body as an insertable pattern in
 * the block editor (category: "ARC Starter Templates").
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gutenberg pattern registration.
 */
final class Arc_ST_Patterns {

	const CATEGORY = 'arc-starter-templates';
	const MARKER   = 'arc-tpl';

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the pattern category and one pattern per template.
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		// Patterns are only consumed by the block/site editor (wp-admin) or the
		// editor's own REST calls — skip the file reads/regex work entirely on
		// plain front-end requests, which never need them.
		$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;
		if ( ! is_admin() && ! $is_rest ) {
			return;
		}

		register_block_pattern_category(
			self::CATEGORY,
			array( 'label' => __( 'ARC Starter Templates', 'arc-starter-templates' ) )
		);

		foreach ( self::get_pattern_data() as $slug => $pattern ) {
			register_block_pattern( 'arc-starter-templates/' . $slug, $pattern );
		}
	}

	/**
	 * Builds (and caches) the per-slug pattern args, since building them means
	 * reading every template's HTML off disk and running it through several
	 * regexes — expensive to repeat on every admin/editor request.
	 *
	 * @return array<string, array>
	 */
	private static function get_pattern_data() {
		// The override signature refreshes the cache the moment a theme
		// override file is added or edited (see Arc_ST_Templates::file()).
		$cache_key = 'arc_st_patterns_' . ARC_ST_VERSION . '_' . Arc_ST_Templates::overrides_signature() . '_' . Arc_ST_Remote::cache_tag();
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$patterns = array();
		foreach ( Arc_ST_Templates::all() as $slug => $tpl ) {
			// Patterns use bundled plugin assets (no media import on insert)
			// and dead "#" links (empty link map).
			$content = '';
			if ( class_exists( 'DOMDocument' ) ) {
				// Same DOM→blocks conversion the importer runs — inserted
				// patterns are editable block-by-block, not a raw HTML blob.
				$blocks = Arc_ST_Blocks::build( $slug, array(), array() );
				if ( '' !== trim( (string) $blocks ) ) {
					$content = '<!-- wp:group {"tagName":"div","className":"' . self::MARKER . ' font-sans text-slate-600 antialiased"} -->' . "\n"
						. '<div class="wp-block-group ' . self::MARKER . ' font-sans text-slate-600 antialiased">' . "\n"
						. $blocks . '</div>' . "\n" . '<!-- /wp:group -->';
				}
			}
			if ( '' === $content ) {
				$body = Arc_ST_Templates::body_html( $slug, false );
				if ( '' === $body ) {
					continue;
				}
				$body    = Arc_ST_Templates::assets_to_plugin_urls( $body );
				$body    = preg_replace( '/href="[\w-]+\.html"/', 'href="#"', $body );
				$content = '<!-- wp:html -->' . "\n"
					. '<div class="' . self::MARKER . ' font-sans text-slate-600 antialiased">'
					. $body
					. '</div>' . "\n"
					. '<!-- /wp:html -->';
			}

			$demo      = Arc_ST_Templates::demo_of( $slug );
			$demo_meta = $demo ? Arc_ST_Templates::demo( $demo ) : null;

			$patterns[ $slug ] = array(
				'title'         => ( $demo_meta ? $demo_meta['title'] : 'ARC' ) . ' — ' . $tpl['name'],
				'description'   => $tpl['description'],
				// BUG FIX: 'content' was built above but never passed to
				// register_block_pattern(), which requires it — patterns were
				// registering empty/rejected.
				'content'       => $content,
				'categories'    => array( self::CATEGORY ),
				'keywords'      => array( 'arc', 'starter', 'landing', 'marketing' ),
				'viewportWidth' => 1280,
				'blockTypes'    => array( 'core/post-content' ),
			);
		}

		set_transient( $cache_key, $patterns, WEEK_IN_SECONDS );
		return $patterns;
	}
}
