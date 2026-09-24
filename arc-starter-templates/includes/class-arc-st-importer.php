<?php
/**
 * Importer — creates/updates WP pages from the bundled templates.
 *
 * With Elementor active the page stores a real `_elementor_data` tree (Canvas
 * template) so every text/image is editable in the builder. Without Elementor
 * the body markup lands in post_content inside the active theme.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template → WP page importer.
 */
final class Arc_ST_Importer {

	const OPTION    = 'arc_st_page_map';
	const META_SLUG = '_arc_st_slug';

	/**
	 * slug => imported page ID map.
	 *
	 * @return array
	 */
	public static function page_map() {
		return (array) get_option( self::OPTION, array() );
	}

	/**
	 * Imported page ID for a template slug, or 0.
	 *
	 * @param string $slug Template slug.
	 * @return int
	 */
	public static function imported_id( $slug ) {
		$map = self::page_map();
		$id  = isset( $map[ $slug ] ) ? (int) $map[ $slug ] : 0;
		return ( $id && get_post( $id ) ) ? $id : 0;
	}

	/**
	 * Whether the active theme is Lienzo Astra (or a compatible child theme).
	 *
	 * Lienzo Astra renders its own header and footer around page content. The
	 * importer must therefore avoid Elementor Canvas, which intentionally
	 * removes the theme chrome, and must keep the imported content body-only.
	 *
	 * @return bool
	 */
	public static function uses_lienzo_chrome() {
		return defined( 'LIENZOASTRA_VERSION' )
			|| function_exists( 'lienzo_display_header_footer' )
			|| function_exists( 'lienzoastra_customizer_defaults' );
	}

	/**
	 * Imports a single template. Creates or updates its page.
	 *
	 * @param string $slug Template slug.
	 * @return int|WP_Error Page ID.
	 */
	public static function import( $slug ) {
		if ( ! Arc_ST_Templates::get( $slug ) ) {
			return new WP_Error( 'arc_st_bad_slug', 'Unknown template.' );
		}
		$page_id = self::ensure_page( $slug );
		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}
		self::remember( $slug, $page_id );
		return self::fill_page( $slug, $page_id );
	}

	/**
	 * Pass 1 of the import: creates (or reuses) a bare page for each slug and
	 * remembers the slug => ID map. Running this BEFORE any page is filled is
	 * what lets resolve_page_links() turn href="<slug>.html" into real
	 * permalinks instead of dead "#" placeholders.
	 *
	 * @param array $slugs Template slugs.
	 * @return array slug => page ID|WP_Error.
	 */
	public static function prepare( $slugs ) {
		$results = array();
		foreach ( (array) $slugs as $slug ) {
			if ( ! Arc_ST_Templates::get( $slug ) ) {
				continue;
			}
			$page_id = self::ensure_page( $slug );
			if ( ! is_wp_error( $page_id ) ) {
				self::remember( $slug, $page_id );
			}
			$results[ $slug ] = $page_id;
		}
		return $results;
	}

	/**
	 * Imports every template of a demo in manifest order.
	 *
	 * Pass 1 creates all pages so pass 2 can resolve cross-page links.
	 *
	 * @param bool   $set_front Make the demo Home template the site front page.
	 * @param string $demo      Demo id ('' = every template, legacy behaviour).
	 * @return array slug => page ID|WP_Error.
	 */
	public static function import_all( $set_front = true, $demo = '' ) {
		$slugs   = $demo ? Arc_ST_Templates::demo_pages( $demo ) : array_keys( Arc_ST_Templates::all() );
		$results = self::prepare( $slugs );

		foreach ( $results as $slug => $page_id ) {
			if ( ! is_wp_error( $page_id ) ) {
				$results[ $slug ] = self::fill_page( $slug, $page_id );
			}
		}

		$home     = $demo ? Arc_ST_Templates::demo_home( $demo ) : 'index';
		$index_id = isset( $results[ $home ] ) ? $results[ $home ] : 0;
		if ( $set_front && $index_id && ! is_wp_error( $index_id ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $index_id );
		}

		return $results;
	}

	/**
	 * Deletes imported pages and clears their entries in the map.
	 * Imported media is kept (it may already be referenced elsewhere).
	 *
	 * With a $demo_id only that demo's pages and menus are removed — other
	 * imported demos stay untouched. Without one, everything is wiped.
	 *
	 * @param string $demo_id Demo to reset ('' = all imported demos).
	 * @return int Number of deleted pages.
	 */
	public static function reset( $demo_id = '' ) {
		$demo    = Arc_ST_Templates::demo( $demo_id );
		$map     = self::page_map();
		$targets = $demo ? array_intersect_key( $map, array_flip( (array) $demo['pages'] ) ) : $map;

		$deleted = 0;
		foreach ( $targets as $slug => $page_id ) {
			if ( get_post( $page_id ) && wp_delete_post( (int) $page_id, true ) ) {
				$deleted++;
			}
			unset( $map[ $slug ] );
		}
		if ( $map ) {
			update_option( self::OPTION, $map, false );
		} else {
			delete_option( self::OPTION );
		}

		// Drop the plugin-created menus too — their items point at the
		// deleted pages — and free any location they occupied.
		$menu_ids = array();
		if ( $demo ) {
			$menu_names = array( $demo['title'] . ' Main', $demo['title'] . ' Footer' );
		} else {
			$menu_names = array( 'ARC Main', 'ARC Footer' ); // Legacy names.
			foreach ( Arc_ST_Templates::demos() as $d ) {
				$menu_names[] = $d['title'] . ' Main';
				$menu_names[] = $d['title'] . ' Footer';
			}
		}
		foreach ( array_unique( $menu_names ) as $name ) {
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				$menu_ids[] = (int) $menu->term_id;
				wp_delete_nav_menu( $menu->term_id );
			}
		}
		if ( $menu_ids ) {
			$locations = get_nav_menu_locations();
			foreach ( $locations as $loc => $id ) {
				if ( in_array( (int) $id, $menu_ids, true ) ) {
					$locations[ $loc ] = 0;
				}
			}
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		return $deleted;
	}

	/**
	 * Site setup after pages exist: front page, nav menus, brand identity
	 * and Elementor integration. Shared by the AJAX wizard and WP-CLI.
	 *
	 * @param bool   $set_front Assign Home as the site front page.
	 * @param bool   $dry       Report what would happen, write nothing.
	 * @param string $demo_id   Demo being imported ('' = legacy all-templates run).
	 * @return array Details of what was (or would be) configured.
	 */
	public static function setup_site( $set_front = true, $dry = false, $demo_id = '' ) {
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$demo       = Arc_ST_Templates::demo( $demo_id );
		$map        = self::page_map();
		$details    = array();
		$locations  = get_nav_menu_locations();
		$registered = array_keys( (array) get_registered_nav_menus() );

		// Demo-scoped views of the page map: only this demo's pages are
		// considered for menus/front page so importing several demos doesn't
		// cross-wire their links.
		$demo_map   = $demo ? array_intersect_key( $map, array_flip( (array) $demo['pages'] ) ) : $map;
		$home_slug  = $demo ? (string) $demo['home'] : 'index';
		$menu_label = $demo ? $demo['title'] : 'ARC';

		// The imported menu mirrors the template's own header nav: same
		// labels, same order, same subset of pages (plus the header CTA
		// when it points at a page the nav doesn't already list).
		$nav_items = self::demo_nav_items( $demo, $demo_map );
		$menu_map  = array();
		foreach ( $nav_items as $slug => $label ) {
			$menu_map[ $slug ] = $demo_map[ $slug ];
		}
		if ( ! $menu_map ) {
			$menu_map = $demo_map;
		}

		if ( $dry ) {
			return array(
				'front_page'  => $set_front && ! empty( $demo_map[ $home_slug ] ) ? (int) $demo_map[ $home_slug ] : 0,
				'menu'        => $menu_label . ' Main (' . count( $menu_map ) . ' items)',
				'footer_menu' => $menu_label . ' Footer',
				'elementor'   => Arc_ST_Elementor::available(),
				'lienzo'      => self::uses_lienzo_chrome(),
				'logo'        => 'custom_logo + site_icon (if empty)',
				'dry'         => true,
			);
		}

		// 1. Front page → the demo's home template (wizard checkbox).
		if ( $set_front && ! empty( $demo_map[ $home_slug ] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $demo_map[ $home_slug ] );
			$details['front_page'] = (int) $demo_map[ $home_slug ];
		}

		// 2. Navigation menus: the demo's menu takes over the header
		//    location. A previously assigned menu is only unassigned — it is
		//    never deleted — so the swap is reversible in Appearance > Menus.
		// Note: get_nav_menu_locations() only lists ASSIGNED locations — a
		// never-assigned location is absent from the array.
		$details['menu_id'] = self::build_menu( $menu_label . ' Main', $menu_map, $nav_items );
		if ( $details['menu_id'] ) {
			foreach ( array( 'primary', 'main', 'header', 'menu-1' ) as $loc ) {
				if ( in_array( $loc, $registered, true ) ) {
					$locations[ $loc ]        = $details['menu_id'];
					$details['menu_location'] = $loc;
					break;
				}
			}
		}

		// Footer menu — Company links for themes with a footer location.
		// Themes name it differently ('footer', 'menu-2' on Astra-style
		// themes, 'footer-menu'…), so try known slugs first, then any
		// registered location whose slug or label says "footer". Empty
		// locations are preferred, occupied ones reused.
		$footer_slugs = $demo && ! empty( $demo['footer'] )
			? (array) $demo['footer']
			: array_slice( array_diff( array_keys( $demo_map ), array( $home_slug ) ), 0, 5 );
		$footer_map   = array_intersect_key( $demo_map, array_flip( $footer_slugs ) );
		if ( $footer_map ) {
			$details['footer_menu_id'] = self::build_menu( $menu_label . ' Footer', $footer_map );
			$footer_loc                = $details['footer_menu_id'] ? self::find_footer_location( $locations ) : '';
			if ( '' !== $footer_loc ) {
				$locations[ $footer_loc ]   = $details['footer_menu_id'];
				$details['footer_location'] = $footer_loc;
			}
		}

		// Undo earlier pollution: menus with "Automatically add new
		// top-level pages" may have swallowed imported pages on a previous
		// run (before the auto-add hook was suspended in ensure_page()).
		// The demo menus were just rebuilt — strip stray links to imported
		// pages from every other menu.
		self::prune_foreign_menu_items(
			array( $details['menu_id'], isset( $details['footer_menu_id'] ) ? $details['footer_menu_id'] : 0 ),
			$demo_map
		);

		set_theme_mod( 'nav_menu_locations', $locations );

		// 3. Brand identity: logo + site icon (only when the theme/site has
		//    none — never overwrite an existing identity).
		$details['logo'] = self::assign_brand_identity( $demo );

		// 4. Elementor: flexbox containers + kit globals + CSS cache rebuild.
			if ( Arc_ST_Elementor::available() ) {
				update_option( 'elementor_experiment-container', 'active' );
				$details['elementor']        = true;
				$details['elementor_colors'] = self::elementor_kit_globals( $demo );
				$details['lienzo']           = self::uses_lienzo_chrome();
				if ( $details['lienzo'] ) {
					$details['lienzo_kit'] = self::configure_lienzo_kit();
				}

			// Verify the experiment actually engaged; if the experiments
			// manager rejected it, imported pages would render boxed.
			$details['elementor_containers'] = Arc_ST_Elementor::available();
			if ( ! $details['elementor_containers'] ) {
				Arc_ST_State::report_add( 'errors', 'Elementor container experiment could not be enabled — pages may need manual layout fixes.' );
			}

			// Rebuild compiled CSS so the first render isn't unstyled.
				if ( isset( \Elementor\Plugin::$instance->files_manager )
				&& method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
				$details['elementor_cache'] = true;
			}
		}

		return $details;
	}

	/**
	 * Sets the theme custom logo and the site icon from the imported demo
	 * logo — only when nothing is configured yet.
	 *
	 * @param array|null $demo Demo meta (null = legacy ARC defaults).
	 * @return array What was assigned.
	 */
	public static function assign_brand_identity( $demo = null ) {
		$assigned   = array();
		$map        = Arc_ST_Media::map();
		$logo_id    = 0;
		$candidates = array();
		if ( $demo && ! empty( $demo['logo'] ) ) {
			$candidates[] = basename( (string) $demo['logo'] );
		}
		foreach ( array_merge( $candidates, array( 'logo-green.png', 'logo.png', 'logo-green.svg', 'logo.svg' ) ) as $candidate ) {
			if ( isset( $map[ $candidate ] ) ) {
				$logo_id = (int) $map[ $candidate ];
				break;
			}
		}
		if ( ! $logo_id ) {
			return $assigned;
		}

		if ( current_theme_supports( 'custom-logo' ) && ! (int) get_theme_mod( 'custom_logo' ) ) {
			set_theme_mod( 'custom_logo', $logo_id );
			$assigned[] = 'custom_logo';
		}
		// Keep the imported logo header-sized on the lienzo-astra theme —
		// the theme's unconstrained fallback is far too big. Only set when
		// the admin hasn't already picked a width.
		if ( self::uses_lienzo_chrome() ) {
			if ( ! (int) get_theme_mod( 'lienzoastra_logo_width', 0 ) ) {
				set_theme_mod( 'lienzoastra_logo_width', 48 );
				$assigned[] = 'logo_width';
			}
			if ( ! (int) get_theme_mod( 'lienzoastra_logo_width_mobile', 0 ) ) {
				set_theme_mod( 'lienzoastra_logo_width_mobile', 40 );
				$assigned[] = 'logo_width_mobile';
			}
		}
		if ( ! (int) get_option( 'site_icon' ) ) {
			// Site icon needs a square image — try the logo, WP will crop.
			update_option( 'site_icon', $logo_id );
			$assigned[] = 'site_icon';
		}
		return $assigned;
	}

	/**
	 * Picks the first empty registered location that looks like a footer:
	 * known slugs first, then any registered location whose slug or label
	 * contains "footer".
	 *
	 * @param array $locations Currently assigned locations (location => menu_id).
	 * @return string Location slug, or '' when none is free.
	 */
	private static function find_footer_location( $locations ) {
		$registered = (array) get_registered_nav_menus();
		$known      = array( 'footer', 'menu-2', 'footer-menu', 'footer-1', 'secondary', 'bottom' );

		// Prefer an empty footer-ish location…
		foreach ( $known as $loc ) {
			if ( isset( $registered[ $loc ] ) && empty( $locations[ $loc ] ) ) {
				return $loc;
			}
		}

		foreach ( $registered as $loc => $label ) {
			if ( ! empty( $locations[ $loc ] ) ) {
				continue;
			}
			if ( false !== stripos( (string) $loc, 'footer' ) || false !== stripos( (string) $label, 'footer' ) ) {
				return $loc;
			}
		}

		// …then reuse an occupied footer location — the imported demo's menu
		//    should be the one visitors see (the old menu is only unassigned).
		foreach ( $known as $loc ) {
			if ( isset( $registered[ $loc ] ) ) {
				return $loc;
			}
		}

		foreach ( $registered as $loc => $label ) {
			if ( false !== stripos( (string) $loc, 'footer' ) || false !== stripos( (string) $label, 'footer' ) ) {
				return $loc;
			}
		}

		return '';
	}

	/**
	 * Removes top-level items pointing at this demo's imported pages from
	 * menus the plugin does not own.
	 *
	 * Menus with "Automatically add new top-level pages" enabled swallow
	 * every page the importer publishes — on sites that already had a menu
	 * this produced a merged header nav (old items + demo items). Only
	 * top-level items are touched: auto-add never creates children, so
	 * hand-built submenu links survive.
	 *
	 * @param array $keep_menu_ids Menu term_ids to skip (the demo's own menus).
	 * @param array $demo_map      slug => page_id for the imported demo.
	 */
	private static function prune_foreign_menu_items( $keep_menu_ids, $demo_map ) {
		$page_ids = array_map( 'intval', array_values( (array) $demo_map ) );
		if ( ! $page_ids ) {
			return;
		}
		$keep = array_map( 'intval', (array) $keep_menu_ids );
		foreach ( (array) wp_get_nav_menus() as $menu ) {
			if ( in_array( (int) $menu->term_id, $keep, true ) ) {
				continue;
			}
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				if ( ! empty( $item->menu_item_parent ) ) {
					continue;
				}
				if ( 'post_type' === $item->type && in_array( (int) $item->object_id, $page_ids, true ) ) {
					wp_delete_post( $item->ID, true );
				}
			}
		}
	}

	/**
	 * Nav items shown by the template's own header, in display order.
	 *
	 * Parses the demo home template's <header> markup: links inside the
	 * first <nav> keep their order and visible label; a header CTA anchor
	 * (btn-* class) is appended when it targets a demo page the nav does
	 * not already include. Anchors whose href does not resolve to an
	 * imported page (external URLs, #anchors) are skipped.
	 *
	 * @param array|null $demo     Demo meta (null = legacy ARC defaults).
	 * @param array      $demo_map slug => page_id for the imported demo.
	 * @return array slug => label, in template order. Empty when unreadable.
	 */
	private static function demo_nav_items( $demo, $demo_map ) {
		$items = array();
		$html  = '';
		if ( $demo && ! empty( $demo['home'] ) ) {
			$html = Arc_ST_Templates::full_html( (string) $demo['home'] );
		}
		if ( '' === $html && $demo && ! empty( $demo['pages'] ) ) {
			$html = Arc_ST_Templates::full_html( (string) $demo['pages'][0] );
		}
		if ( '' === $html || ! preg_match( '/<header\b[^>]*>(.*?)<\/header>/s', $html, $hm ) ) {
			return $items;
		}

		$add = function ( $href, $inner ) use ( &$items, $demo_map ) {
			$slug = preg_replace( '/\.html?$/i', '', basename( $href ) );
			if ( '' === $slug || ! isset( $demo_map[ $slug ] ) || isset( $items[ $slug ] ) ) {
				return;
			}
			$label = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES ) ) );
			$items[ $slug ] = '' !== $label ? $label : $slug;
		};

		if ( preg_match( '/<nav\b[^>]*>(.*?)<\/nav>/s', $hm[1], $nm ) ) {
			preg_match_all( '/<a\b[^>]*href="([^"#]+\.html?)"[^>]*>(.*?)<\/a>/s', $nm[1], $am, PREG_SET_ORDER );
			foreach ( $am as $a ) {
				$add( $a[1], $a[2] );
			}
		}

		// Header CTA button — part of the template's visible menu.
		preg_match_all( '/<a\b[^>]*href="([^"#]+\.html?)"[^>]*class="[^"]*btn[^"]*"[^>]*>(.*?)<\/a>/s', $hm[1], $cm, PREG_SET_ORDER );
		foreach ( $cm as $a ) {
			$add( $a[1], $a[2] );
		}

		return $items;
	}

	/**
	 * Creates or reuses a nav menu and (re)builds its items from the map.
	 *
	 * @param string $name   Menu name.
	 * @param array  $map    slug => page_id.
	 * @param array  $titles slug => custom item label (template nav labels).
	 * @return int Menu term_id (0 on failure).
	 */
	public static function build_menu( $name, $map, $titles = array() ) {
		$existing_menu = wp_get_nav_menu_object( $name );
		$menu_id       = $existing_menu ? (int) $existing_menu->term_id : wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) || ! $menu_id ) {
			return 0;
		}
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		foreach ( $map as $slug => $page_id ) {
			$meta = Arc_ST_Templates::get( $slug );
			if ( ! $meta ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => isset( $titles[ $slug ] ) ? $titles[ $slug ] : $meta['name'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		return (int) $menu_id;
	}

	/**
	 * Writes the demo palette and typography into the active Elementor kit so
	 * they appear as global swatches/fonts when editing widgets.
	 *
	 * @param array|null $demo Demo meta (null = legacy ARC defaults).
	 * @return bool Whether globals were written.
	 */
	public static function elementor_kit_globals( $demo = null ) {
		$kit_id = (int) get_option( 'elementor_active_kit' );
		if ( ! $kit_id ) {
			return false;
		}

		$kit = ( $demo && ! empty( $demo['kit'] ) && is_array( $demo['kit'] ) ) ? $demo['kit'] : array();

		$brand = isset( $kit['colors'] ) && is_array( $kit['colors'] ) ? $kit['colors'] : array(
			array( '_id' => 'arc_brand', 'title' => 'ARC Brand', 'color' => '#4c9464' ),
			array( '_id' => 'arc_leaf',  'title' => 'ARC Leaf',  'color' => '#59a875' ),
			array( '_id' => 'arc_mint',  'title' => 'ARC Mint',  'color' => '#e6f2ea' ),
			array( '_id' => 'arc_navy',  'title' => 'ARC Navy',  'color' => '#142a1e' ),
			array( '_id' => 'arc_gold',  'title' => 'ARC Gold',  'color' => '#c9a227' ),
		);
		$fonts = isset( $kit['fonts'] ) && is_array( $kit['fonts'] ) ? $kit['fonts'] : array(
			array(
				'_id'                    => 'arc_body',
				'title'                  => 'ARC Body',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Outfit',
				'typography_font_weight' => '400',
			),
			array(
				'_id'                    => 'arc_heading',
				'title'                  => 'ARC Heading',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Outfit',
				'typography_font_weight' => '700',
			),
		);

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		$settings = is_array( $settings ) ? $settings : array();

		foreach ( array( 'custom_colors' => $brand, 'custom_typography' => $fonts ) as $key => $items ) {
			$existing = isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : array();
			$have     = wp_list_pluck( $existing, '_id' );
			foreach ( $items as $item ) {
				if ( ! in_array( $item['_id'], $have, true ) ) {
					$existing[] = $item;
				}
			}
			$settings[ $key ] = $existing;
		}

		// Elementor stores page settings as a plain array meta — not JSON.
		update_post_meta( $kit_id, '_elementor_page_settings', $settings );
		return true;
	}

	/**
	 * Makes Lienzo Astra's native header and footer visible when Elementor is
	 * active. Only missing values are added, so an existing kit configuration is
	 * never replaced by an import.
	 *
	 * @return bool Whether the active kit was updated.
	 */
	private static function configure_lienzo_kit() {
		if ( ! defined( 'LIENZOASTRA_VERSION' ) || ! function_exists( 'lienzo_display_header_footer' ) ) {
			return false;
		}

		$kit_id = (int) get_option( 'elementor_active_kit' );
		if ( ! $kit_id ) {
			return false;
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		$settings = is_array( $settings ) ? $settings : array();
		$defaults = array(
			'lienzo_header_logo_display'   => 'yes',
			'lienzo_header_menu_display'   => 'yes',
			'lienzo_footer_logo_display'   => 'yes',
			'lienzo_footer_menu_display'   => 'yes',
			'lienzo_footer_copyright_display' => 'yes',
		);
		$changed = false;

		foreach ( $defaults as $key => $value ) {
			if ( ! array_key_exists( $key, $settings ) || '' === $settings[ $key ] ) {
				$settings[ $key ] = $value;
				$changed       = true;
			}
		}

		if ( $changed ) {
			update_post_meta( $kit_id, '_elementor_page_settings', $settings );
		}
		return $changed;
	}

	/**
	 * Finds the existing page for a slug or creates a bare one.
	 *
	 * @param string $slug Template slug.
	 * @return int|WP_Error
	 */
	private static function ensure_page( $slug ) {
		$existing = self::imported_id( $slug );
		if ( $existing ) {
			return $existing;
		}
		$meta = Arc_ST_Templates::get( $slug );
		// Menus with "Automatically add new top-level pages" enabled would
		// otherwise swallow every imported page — the demo builds its own
		// menus, so suspend core's auto-add while the page is published.
		remove_action( 'transition_post_status', '_wp_auto_add_pages_to_menu' );
		// Suspend KSES too: imports can run with no logged-in user (WP-CLI,
		// provisioning scripts), where unfiltered_html is absent and every
		// <style>/<form>/inline handler in the template would be stripped.
		kses_remove_filters();
		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $meta['name'],
				'post_name'      => 'index' === $slug ? 'home' : $slug,
				'post_content'   => '',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);
		add_action( 'transition_post_status', '_wp_auto_add_pages_to_menu', 10, 3 );
		kses_init_filters();
		return $page_id;
	}

	/**
	 * Stores slug => ID in the map option.
	 *
	 * @param string $slug    Template slug.
	 * @param int    $page_id Page ID.
	 */
	private static function remember( $slug, $page_id ) {
		$map          = self::page_map();
		$map[ $slug ] = (int) $page_id;
		update_option( self::OPTION, $map, false );
	}

	/**
	 * Builds and stores the page content (Elementor data + HTML fallback).
	 *
	 * @param string $slug    Template slug.
	 * @param int    $page_id Page ID.
	 * @return int|WP_Error
	 */
	private static function fill_page( $slug, $page_id ) {
		$link_map  = self::page_map();
		$media_map = Arc_ST_Media::import_for_template( $slug );

		// Fallback/fallback content: body without the site header/footer.
		// Preferred shape: native Gutenberg blocks (editable with or without
		// Elementor). Fallback when ext-dom is missing: one wp:html block.
		$content = class_exists( 'DOMDocument' )
			? Arc_ST_Blocks::build( $slug, $media_map, $link_map )
			: '';
		if ( '' !== trim( (string) $content ) ) {
			$content = '<!-- wp:group {"tagName":"div","className":"arc-tpl font-sans text-slate-600 antialiased"} -->' . "\n"
				. '<div class="wp-block-group arc-tpl font-sans text-slate-600 antialiased">' . "\n"
				. $content . '</div>' . "\n" . '<!-- /wp:group -->';
		} else {
			$body = Arc_ST_Templates::body_html( $slug, false );
			$body = Arc_ST_Templates::assets_to_media_urls( $body, $media_map );
			$body = Arc_ST_Templates::resolve_page_links( $body, $link_map );
			$content = '<!-- wp:html -->' . "\n"
				. '<div class="arc-tpl font-sans text-slate-600 antialiased">' . $body . '</div>' . "\n"
				. '<!-- /wp:html -->';
		}

		$post = array(
			'ID'           => $page_id,
			'post_content' => $content,
		);
		// The serialized markup carries <style>, <form> and onclick handlers by
		// design; without a privileged user in context KSES would strip them
		// and leak raw CSS as visible text. Suspend it for the write.
		kses_remove_filters();
		$ok   = wp_update_post( $post, true );
		kses_init_filters();
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		update_post_meta( $page_id, self::META_SLUG, $slug );

		if ( Arc_ST_Elementor::available() ) {
			$elements = Arc_ST_Elementor::build( $slug, $media_map, $link_map );
			update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
			update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
			}
			delete_post_meta( $page_id, '_elementor_css' );
				// Lienzo Astra supplies the site chrome. Canvas would remove its
				// header/footer, so keep the theme's normal page template there.
				update_post_meta( $page_id, '_wp_page_template', self::uses_lienzo_chrome() ? 'default' : 'elementor_canvas' );
		} else {
			// Classic-mode import over a page that previously had Elementor
			// data: clear it, or a reactivated Elementor would resurrect the
			// stale builder instead of the content we just wrote.
			delete_post_meta( $page_id, '_elementor_data' );
			delete_post_meta( $page_id, '_elementor_edit_mode' );
			delete_post_meta( $page_id, '_elementor_template_type' );
			if ( 'elementor_canvas' === get_post_meta( $page_id, '_wp_page_template', true ) ) {
				update_post_meta( $page_id, '_wp_page_template', 'default' );
			}
		}

		return (int) $page_id;
	}
}
