<?php
/**
 * Template registry — reads templates/manifest.json and the built page files.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Access layer over the generated templates/ payload.
 */
final class Arc_ST_Templates {

	/**
	 * Cached manifest data.
	 *
	 * @var array|null
	 */
	private static $manifest = null;

	/**
	 * Decoded templates/manifest.json.
	 *
	 * @return array
	 */
	public static function manifest() {
		if ( null === self::$manifest ) {
			$file = '';
			// Remote mode: the manifest lives in the repo; the bundled copy
			// (if the plugin still ships one) is only a fallback.
			if ( Arc_ST_Remote::enabled() ) {
				$file = Arc_ST_Remote::fetch( 'templates/manifest.json' );
			}
			if ( '' === $file ) {
				$file = ARC_ST_PATH . 'templates/manifest.json';
			}
			self::$manifest = is_readable( $file )
				? (array) json_decode( (string) file_get_contents( $file ), true )
				: array( 'templates' => array() );
		}
		return self::$manifest;
	}

	/**
	 * All demos (importable sites): id => meta.
	 *
	 * Each demo groups a set of templates under one library card. When the
	 * manifest has no "demos" section every template is treated as part of the
	 * single bundled demo (backward compatible with the original format).
	 *
	 * @return array
	 */
	public static function demos() {
		$manifest = self::manifest();
		$demos    = isset( $manifest['demos'] ) ? (array) $manifest['demos'] : array();

		if ( ! $demos ) {
			$demos = array(
				'arc-site' => array(
					'title' => 'Ash River Collective',
					'pages' => array_keys( self::all() ),
					'home'  => 'index',
				),
			);
		}

		foreach ( $demos as $id => $meta ) {
			$pages = isset( $meta['pages'] ) ? array_values( array_filter( (array) $meta['pages'], array( __CLASS__, 'get' ) ) ) : array();
			$demos[ $id ] = wp_parse_args(
				(array) $meta,
				array(
					'title'      => $id,
					'desc'       => '',
					'categories' => array(),
					'image'      => '',
					'home'       => isset( $pages[0] ) ? $pages[0] : '',
					'pages'      => $pages,
					'footer'     => array(),
					'logo'       => '',
					'kit'        => array(),
					'source'     => '',
				)
			);
			// wp_parse_args doesn't deep-fill 'pages' from the filtered list.
			$demos[ $id ]['pages'] = $pages;
		}
		return $demos;
	}

	/**
	 * One demo's meta or null.
	 *
	 * @param string $id Demo id.
	 * @return array|null
	 */
	public static function demo( $id ) {
		$demos = self::demos();
		return isset( $demos[ $id ] ) ? $demos[ $id ] : null;
	}

	/**
	 * Template slugs belonging to a demo (manifest order).
	 *
	 * @param string $id Demo id.
	 * @return array
	 */
	public static function demo_pages( $id ) {
		$demo = self::demo( $id );
		return $demo ? (array) $demo['pages'] : array();
	}

	/**
	 * The demo's "home" template slug (used for preview + front page).
	 *
	 * @param string $id Demo id.
	 * @return string
	 */
	public static function demo_home( $id ) {
		$demo = self::demo( $id );
		return $demo ? (string) $demo['home'] : '';
	}

	/**
	 * Demo id a template slug belongs to, or '' when unassigned.
	 *
	 * @param string $slug Template slug.
	 * @return string
	 */
	public static function demo_of( $slug ) {
		foreach ( self::demos() as $id => $demo ) {
			if ( in_array( $slug, (array) $demo['pages'], true ) ) {
				return $id;
			}
		}
		return '';
	}

	/**
	 * All templates: slug => meta.
	 *
	 * @return array
	 */
	public static function all() {
		$manifest   = self::manifest();
		$templates  = isset( $manifest['templates'] ) ? (array) $manifest['templates'] : array();
		foreach ( $templates as $slug => $meta ) {
			$templates[ $slug ] = wp_parse_args(
				(array) $meta,
				array(
					'name'        => $slug,
					'title'       => $slug,
					'description' => '',
					'file'        => $slug . '.html',
					'thumb'       => '',
				)
			);
		}
		return $templates;
	}

	/**
	 * One template's meta or null.
	 *
	 * @param string $slug Template slug.
	 * @return array|null
	 */
	public static function get( $slug ) {
		$all = self::all();
		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Absolute path to the built template document.
	 *
	 * Templates may live in per-demo subfolders (templates/<demo>/<page>.html);
	 * the manifest "file" field carries the relative path. Traversal segments
	 * are stripped so a manifest value can never leave templates/.
	 *
	 * Theme override (same convention WooCommerce uses): when the active
	 * theme ships arc-starter-templates/<file> — e.g.
	 * wp-content/themes/lienzo-astra/arc-starter-templates/arc-site/index.html —
	 * that file is used for previews, imports and patterns instead of the
	 * bundled copy, so a template can be edited inside the theme and
	 * re-imported. locate_template() checks the child theme first, then the
	 * parent.
	 *
	 * @param string $slug Template slug.
	 * @return string
	 */
	public static function file( $slug ) {
		$name = self::rel_name( $slug );

		$override = function_exists( 'locate_template' )
			? locate_template( 'arc-starter-templates/' . $name )
			: '';

		if ( '' !== $override && is_readable( $override ) ) {
			$path = $override;
		} else {
			// Remote mode downloads the document into the disk cache; the
			// bundled copy (if any) remains the offline fallback.
			$path = '';
			if ( Arc_ST_Remote::enabled() ) {
				$path = Arc_ST_Remote::fetch( 'templates/' . $name );
			}
			if ( '' === $path ) {
				$path = ARC_ST_PATH . 'templates/' . $name;
			}
		}

		/**
		 * Filters the resolved template document path.
		 *
		 * @param string $path Absolute path that will be read.
		 * @param string $slug Template slug.
		 * @param string $name Manifest-relative file name (e.g. arc-site/index.html).
		 */
		return (string) apply_filters( 'arc_st_template_file', $path, $slug, $name );
	}

	/**
	 * Repo/plugin-relative document path for a template slug.
	 *
	 * @param string $slug Template slug.
	 * @return string e.g. "printifix/home.html".
	 */
	private static function rel_name( $slug ) {
		$meta = self::get( $slug );
		$name = $meta ? $meta['file'] : $slug . '.html';
		$name = ltrim( str_replace( '\\', '/', (string) $name ), '/' );
		return (string) preg_replace( '#(?:^|/)\.\.(?=/|$)#', '', $name );
	}

	/**
	 * Whether the active theme overrides a template's bundled document.
	 *
	 * @param string $slug Template slug.
	 * @return bool
	 */
	public static function overridden( $slug ) {
		// Test the theme-override path directly — in remote mode the download
		// cache also lives outside ARC_ST_PATH, so a path-prefix check on
		// file() would report every remote template as overridden.
		$override = function_exists( 'locate_template' )
			? locate_template( 'arc-starter-templates/' . self::rel_name( $slug ) )
			: '';
		return '' !== $override && is_readable( $override );
	}

	/**
	 * Templates the active theme overrides: slug => override path.
	 *
	 * @return array<string,string>
	 */
	public static function overrides() {
		$out = array();
		foreach ( self::all() as $slug => $meta ) {
			if ( self::overridden( $slug ) ) {
				$out[ $slug ] = self::file( $slug );
			}
		}
		return $out;
	}

	/**
	 * Signature of the current theme overrides (path + mtime per file).
	 * Lets cached artefacts (e.g. registered block patterns) refresh the
	 * moment a theme override is added or edited.
	 *
	 * @return string
	 */
	public static function overrides_signature() {
		$parts = array();
		foreach ( self::overrides() as $slug => $path ) {
			$parts[] = $slug . ':' . ( is_readable( $path ) ? (string) filemtime( $path ) : '0' );
		}
		return md5( implode( '|', $parts ) );
	}

	/**
	 * Full rendered HTML document for a template.
	 *
	 * @param string $slug Template slug.
	 * @return string Empty string when missing.
	 */
	public static function full_html( $slug ) {
		$file = self::file( $slug );
		$html = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
		return self::runtime_fixes( $html );
	}

	/**
	 * Inner <body> markup of a template.
	 *
	 * @param string $slug        Template slug.
	 * @param bool   $with_chrome Keep the site <header>/<footer> partials.
	 * @return string
	 */
	public static function body_html( $slug, $with_chrome = true ) {
		$html = self::full_html( $slug );
		if ( '' === $html || ! preg_match( '/<body\b[^>]*>(.*)<\/body>/s', $html, $m ) ) {
			return '';
		}
		$body = $m[1];
		if ( ! $with_chrome ) {
			// Theme supplies the chrome — drop the site header/footer partials.
			$body = preg_replace( '/<header\b[^>]*>.*?<\/header>/s', '', $body, 1 );
			$body = preg_replace( '/<footer\b[^>]*>.*?<\/footer>/s', '', $body, 1 );
		}
		return trim( (string) $body );
	}

	/**
	 * Rewrites inline handler names to the plugin's prefixed JS globals so the
	 * markup can never collide with another plugin/theme function of the same
	 * name. Applies to every markup path (preview, import, patterns).
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function runtime_fixes( $html ) {
		$html = strtr(
			$html,
			array(
				'toggleMobileNav('       => 'arcStToggleMobileNav(',
				'handleContactSubmit('   => 'arcStHandleContactSubmit(',
			)
		);
		$html = self::wire_contact_form( $html );
		return self::wire_subscribe_forms( $html );
	}

	/**
	 * Makes the contact template's form submit for real: posts to admin-post
	 * with a honeypot instead of the JS-only success swap.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function wire_contact_form( $html ) {
		if ( false === strpos( $html, 'id="contact-form"' ) ) {
			return $html;
		}

		// method/action + drop the JS-only onsubmit.
		$html = preg_replace(
			'/<form\b([^>]*?)id="contact-form"([^>]*?)onsubmit="[^"]*"/',
			'<form$1id="contact-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"$2',
			$html,
			1
		);

		// Hidden fields right after the opening tag. No nonce on purpose:
		// public form — nonces expire and break under page caches. Protection
		// = honeypot + full server-side sanitization (like core's comments).
		$hidden  = '<input type="hidden" name="action" value="arc_st_contact" />';
		$hidden .= '<input type="text" name="arc_st_hp" value="" tabindex="-1" autocomplete="off" '
			. 'style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true" />';
		$html = preg_replace(
			'/(<form\b[^>]*id="contact-form"[^>]*>)/',
			'$1' . $hidden,
			$html,
			1
		);

		// name="" on every field so values reach POST.
		$names = 'first-name|last-name|company|phone|work-email|need|count|when|details';
		$html  = preg_replace(
			'/<(input|select|textarea)\b([^>]*?)\bid="(' . $names . ')"/',
			'<$1$2id="$3" name="$3"',
			$html
		);

		// Optional consent checkbox (GDPR) — injected before the submit button
		// when the admin configured a consent text in the library settings.
		$consent = trim( (string) get_option( 'arc_st_consent_text', '' ) );
		if ( '' !== $consent ) {
			$checkbox = '<label class="flex items-start gap-2 text-sm text-slate-600">'
				. '<input type="checkbox" name="arc_st_consent" value="1" required class="mt-1" />'
				. '<span>' . esc_html( $consent ) . '</span></label>';
			$html = preg_replace(
				'/(<form\b[^>]*id="contact-form"[^>]*>.*?)(<button\b)/s',
				'$1' . $checkbox . '$2',
				$html,
				1
			);
		}

		return $html;
	}

	/**
	 * Wires the newsletter/notify forms (class="arc-st-subscribe") to really
	 * submit to admin-post.php?action=arc_st_subscribe — honeypot included.
	 * The demo-only onsubmit swap is dropped.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function wire_subscribe_forms( $html ) {
		if ( false === strpos( $html, 'arc-st-subscribe' ) ) {
			return $html;
		}

		// method/action + drop the JS-only onsubmit (on EVERY subscribe form).
		$html = preg_replace(
			'/<form\b([^>]*?\barc-st-subscribe\b[^>]*?)\s*onsubmit="[^"]*"/',
			'<form$1 method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"',
			$html
		);

		// Hidden fields right after the opening tag — no nonce on purpose
		// (public form under page caches); protection = honeypot + rate limit.
		$hidden = '<input type="hidden" name="action" value="arc_st_subscribe" />'
			. '<input type="text" name="arc_st_hp" value="" tabindex="-1" autocomplete="off" '
			. 'style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true" />';
		$html = preg_replace(
			'/(<form\b[^>]*\barc-st-subscribe\b[^>]*>)/',
			'$1' . $hidden,
			$html
		);

		// Optional consent checkbox (GDPR) — appended inside each form when the
		// admin configured a consent text in the library settings.
		$consent = trim( (string) get_option( 'arc_st_consent_text', '' ) );
		if ( '' !== $consent ) {
			$checkbox = '<div class="mt-3 text-left"><label class="flex items-start gap-2 text-sm text-slate-600">'
				. '<input type="checkbox" name="arc_st_consent" value="1" required class="mt-1" />'
				. '<span>' . esc_html( $consent ) . '</span></label></div>';
			$html = preg_replace(
				'/(<form\b[^>]*\barc-st-subscribe\b[^>]*>.*?)(<\/form>)/s',
				'$1' . $checkbox . '$2',
				$html
			);
		}

		return $html;
	}

	/**
	 * Rewrites bundled relative asset paths (Img/, Css/, Js/) to plugin URLs.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function assets_to_plugin_urls( $html ) {
		// Images live in the repo in remote mode; Css/Js stay bundled — they
		// are the runtime, not template content.
		$img = Arc_ST_Remote::enabled() ? Arc_ST_Remote::base() . '/assets/img/' : ARC_ST_URL . 'assets/img/';
		return strtr(
			$html,
			array(
				'="Img/'            => '="' . $img,
				'="Css/tailwind.css' => '="' . ARC_ST_URL . 'assets/css/tailwind.css',
				'="Js/site.js'      => '="' . ARC_ST_URL . 'assets/js/site.js',
			)
		);
	}

	/**
	 * Rewrites bundled relative asset paths to Media Library URLs.
	 *
	 * @param string $html      Markup.
	 * @param array  $media_map filename => array{id:int,url:string}.
	 * @return string
	 */
	public static function assets_to_media_urls( $html, $media_map ) {
		$img = Arc_ST_Remote::enabled() ? Arc_ST_Remote::base() . '/assets/img/' : ARC_ST_URL . 'assets/img/';
		return preg_replace_callback(
			'/="Img\/([^"]+)"/',
			function ( $m ) use ( $media_map, $img ) {
				$file = rawurldecode( $m[1] );
				return isset( $media_map[ $file ] )
					? '="' . esc_url( $media_map[ $file ]['url'] ) . '"'
					: '="' . $img . $m[1] . '"';
			},
			$html
		);
	}

	/**
	 * Rewrites href="<slug>.html" links to imported page permalinks.
	 *
	 * @param string $html     Markup.
	 * @param array  $page_map slug => WP_Post ID.
	 * @return string
	 */
	public static function resolve_page_links( $html, $page_map ) {
		return preg_replace_callback(
			'/href="([\w-]+)\.html"/',
			function ( $m ) use ( $page_map ) {
				$slug = $m[1];
				if ( isset( $page_map[ $slug ] ) && get_post( $page_map[ $slug ] ) ) {
					return 'href="' . esc_url( get_permalink( $page_map[ $slug ] ) ) . '"';
				}
				return 'href="#"';
			},
			$html
		);
	}

	/**
	 * Plugin asset URL for a manifest thumb path.
	 *
	 * @param string $slug Template slug.
	 * @return string URL or ''.
	 */
	public static function thumb_url( $slug ) {
		$meta = self::get( $slug );
		return ( $meta && $meta['thumb'] ) ? self::asset_url( $meta['thumb'] ) : '';
	}

	/**
	 * Resolves a manifest asset path to a URL — absolute http(s) URLs pass
	 * through untouched, relative paths are plugin-local.
	 *
	 * @param string $path Manifest asset path or URL.
	 * @return string
	 */
	public static function asset_url( $path ) {
		$path = (string) $path;
		if ( preg_match( '#^https?://#i', $path ) ) {
			return $path;
		}
		// Remote mode serves repo assets (card images, thumbs) straight from
		// the repo URL instead of the plugin directory.
		return Arc_ST_Remote::enabled() ? Arc_ST_Remote::url( $path ) : ARC_ST_URL . $path;
	}

	/**
	 * Card image URL for a demo (bundled path or absolute URL).
	 *
	 * @param string $id Demo id.
	 * @return string URL or ''.
	 */
	public static function demo_image_url( $id ) {
		$demo = self::demo( $id );
		return ( $demo && $demo['image'] ) ? self::asset_url( $demo['image'] ) : '';
	}

	/**
	 * Slugs of every image referenced by a template document.
	 *
	 * @param string $slug Template slug.
	 * @return array Filenames (basename only).
	 */
	public static function image_files( $slug ) {
		$html = self::full_html( $slug );
		preg_match_all( '/Img\/([^"\'\s>]+)/', $html, $m );
		return array_values( array_unique( array_map( 'rawurldecode', $m[1] ) ) );
	}

	/**
	 * Alt text per bundled image, read from the template markup.
	 *
	 * @param string $slug Template slug.
	 * @return array filename => alt text.
	 */
	public static function image_alts( $slug ) {
		$html = self::full_html( $slug );
		$alts = array();
		if ( preg_match_all( '/<img\b[^>]*>/i', $html, $tags ) ) {
			foreach ( $tags[0] as $tag ) {
				if ( preg_match( '/Img\/([^"\'\s>]+)/', $tag, $src )
					&& preg_match( '/\balt="([^"]*)"/', $tag, $alt )
					&& '' !== $alt[1] ) {
					$alts[ rawurldecode( $src[1] ) ] = html_entity_decode( $alt[1], ENT_QUOTES, 'UTF-8' );
				}
			}
		}
		return $alts;
	}
}
