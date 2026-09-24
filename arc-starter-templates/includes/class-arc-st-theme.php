<?php
/**
 * Theme sync — exposes the imported demo's design tokens (palette +
 * typography) to the block editor through the documented theme.json data
 * filters, so imported pages are editable with real site presets instead of
 * hard-coded colors.
 *
 * Mechanism (WP 6.1+, block-editor handbook "theme.json data filters"):
 *   wp_theme_json_data_theme → presets become part of the theme layer —
 *   they appear in every color/font picker and emit the matching
 *   --wp--preset--* CSS custom properties on the front end.
 *   wp_theme_json_data_user  → same merge on the user layer, so the presets
 *   stay visible even when the site has a custom Global Styles palette.
 *
 * Presets are stored per origin and merge() replaces the whole list for
 * that origin — the merge below always rewrites "existing + new" entries.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * theme.json design-token sync.
 */
final class Arc_ST_Theme {

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		// Priority 20: run after the theme's own palette sync (themes like
		// Lienzo Astra rewrite their palette at the default 10) so a theme
		// that replaces the preset list can't wipe the demo tokens.
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'sync_theme' ), 20 );
		add_filter( 'wp_theme_json_data_user', array( __CLASS__, 'sync_user' ), 20 );
	}

	/**
	 * Theme layer merge (origin "theme").
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme layer data.
	 * @return WP_Theme_JSON_Data
	 */
	public static function sync_theme( $theme_json ) {
		return self::inject( $theme_json, 'theme' );
	}

	/**
	 * User layer merge (origin "custom") — keeps the presets available when a
	 * custom Global Styles palette shadows the theme palette.
	 *
	 * @param WP_Theme_JSON_Data $theme_json User layer data.
	 * @return WP_Theme_JSON_Data
	 */
	public static function sync_user( $theme_json ) {
		return self::inject( $theme_json, 'custom' );
	}

	/**
	 * Merges the demo design tokens into one theme.json layer.
	 *
	 * @param WP_Theme_JSON_Data $theme_json Layer data object.
	 * @param string             $origin     'theme' or 'custom'.
	 * @return WP_Theme_JSON_Data
	 */
	private static function inject( $theme_json, $origin ) {
		if ( ! apply_filters( 'arc_st_theme_json_sync', true ) ) {
			return $theme_json;
		}
		if ( ! is_object( $theme_json ) || ! method_exists( $theme_json, 'update_with' ) ) {
			return $theme_json;
		}

		$tokens = self::tokens();
		if ( empty( $tokens['colors'] ) && empty( $tokens['fonts'] ) ) {
			return $theme_json;
		}

		$data  = $theme_json->get_data();
		$patch = array( 'version' => WP_Theme_JSON::LATEST_SCHEMA, 'settings' => array() );

		if ( $tokens['colors'] ) {
			$current = self::presets( $data, array( 'color', 'palette' ), $origin );
			$have    = wp_list_pluck( $current, 'slug' );
			$new     = array();
			foreach ( $tokens['colors'] as $color ) {
				if ( ! in_array( $color['slug'], $have, true ) ) {
					$new[] = $color;
				}
			}
			if ( $new ) {
				$patch['settings']['color']['palette'] = array_merge( $current, $new );
			}
		}

		if ( $tokens['fonts'] ) {
			$current = self::presets( $data, array( 'typography', 'fontFamilies' ), $origin );
			$have    = wp_list_pluck( $current, 'slug' );
			$new     = array();
			foreach ( $tokens['fonts'] as $font ) {
				if ( ! in_array( $font['slug'], $have, true ) ) {
					$new[] = $font;
				}
			}
			if ( $new ) {
				$patch['settings']['typography']['fontFamilies'] = array_merge( $current, $new );
			}
		}

		return empty( $patch['settings'] ) ? $theme_json : $theme_json->update_with( $patch );
	}

	/**
	 * Preset list at a settings path for one origin. Presets are stored keyed
	 * by origin internally; a flat sequential list is accepted too.
	 *
	 * @param array  $data   Raw theme.json data (WP_Theme_JSON_Data::get_data()).
	 * @param array  $path   Path inside "settings" (e.g. array('color','palette')).
	 * @param string $origin 'theme' or 'custom'.
	 * @return array<int,array>
	 */
	private static function presets( $data, $path, $origin ) {
		$node = $data['settings'] ?? array();
		foreach ( $path as $key ) {
			$node = is_array( $node ) && isset( $node[ $key ] ) ? $node[ $key ] : array();
		}

		if ( isset( $node[ $origin ] ) && is_array( $node[ $origin ] ) ) {
			return array_values( $node[ $origin ] );
		}

		// Flat list (not yet keyed by origin).
		if ( is_array( $node ) && array_keys( $node ) === range( 0, count( $node ) - 1 ) ) {
			return array_values( $node );
		}

		return array();
	}

	/**
	 * Color palette + font families collected from the bundled demos' "kit"
	 * section. When an import already ran, only that demo's tokens are synced;
	 * before any import every demo contributes so manually inserted patterns
	 * can be styled too. Demos without a kit fall back to the ARC defaults.
	 *
	 * @return array{colors: array<int,array>, fonts: array<int,array>}
	 */
	private static function tokens() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$demos = Arc_ST_Templates::demos();

		$state_demo = (string) Arc_ST_State::state()['demo'];
		if ( '' !== $state_demo && isset( $demos[ $state_demo ] ) ) {
			$demos = array( $state_demo => $demos[ $state_demo ] );
		}

		$colors = array();
		$fonts  = array();
		$seen_c = array();
		$seen_f = array();

		foreach ( $demos as $demo ) {
			$kit = isset( $demo['kit'] ) && is_array( $demo['kit'] ) ? $demo['kit'] : array();
			$kit = wp_parse_args( $kit, self::default_kit() );

			foreach ( (array) $kit['colors'] as $color ) {
				if ( empty( $color['color'] ) ) {
					continue;
				}
				$slug = sanitize_title( (string) ( $color['_id'] ?? $color['title'] ?? '' ) );
				if ( '' === $slug || isset( $seen_c[ $slug ] ) ) {
					continue;
				}
				$seen_c[ $slug ] = true;
				$colors[]        = array(
					'slug'  => $slug,
					'color' => (string) $color['color'],
					'name'  => (string) ( $color['title'] ?? $slug ),
				);
			}

			foreach ( (array) $kit['fonts'] as $font ) {
				$family = (string) ( $font['typography_font_family'] ?? '' );
				if ( '' === $family ) {
					continue;
				}
				$slug = sanitize_title( (string) ( $font['_id'] ?? $family ) );
				if ( '' === $slug || isset( $seen_f[ $slug ] ) ) {
					continue;
				}
				$seen_f[ $slug ] = true;
				$fonts[]         = array(
					'slug'       => $slug,
					'fontFamily' => $family,
					'name'       => (string) ( $font['title'] ?? $family ),
				);
			}
		}

		$cache = array( 'colors' => $colors, 'fonts' => $fonts );
		return $cache;
	}

	/**
	 * ARC default design tokens — the same palette/typography the Elementor
	 * kit globals fall back to when a demo ships no "kit" section.
	 *
	 * @return array{colors: array, fonts: array}
	 */
	private static function default_kit() {
		return array(
			'colors' => array(
				array( '_id' => 'arc_brand', 'title' => 'ARC Brand', 'color' => '#4c9464' ),
				array( '_id' => 'arc_leaf',  'title' => 'ARC Leaf',  'color' => '#59a875' ),
				array( '_id' => 'arc_mint',  'title' => 'ARC Mint',  'color' => '#e6f2ea' ),
				array( '_id' => 'arc_navy',  'title' => 'ARC Navy',  'color' => '#142a1e' ),
				array( '_id' => 'arc_gold',  'title' => 'ARC Gold',  'color' => '#c9a227' ),
			),
			'fonts'  => array(
				array( '_id' => 'arc_outfit', 'title' => 'ARC Outfit', 'typography_font_family' => 'Outfit' ),
			),
		);
	}
}
