<?php
/**
 * ARC template copy overrides + palette presets.
 *
 * The Customizer "ARC Template" section holds a markdown document with the
 * same structure as the bundled baseline (assets/arc-copy-baseline.md):
 * "# N. PAGE" sections, "## Section" groups, "###" headings, paragraphs and
 * "**CTA:**" lines. Both documents are parsed into slot keys
 * (page|section|kind|index); slots whose text differs produce a
 * baseline-text => new-text pair that is swapped inside the rendered
 * markup of imported ARC template pages (text nodes only — markup and
 * attributes are never touched).
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses the copy markdown into slot => text pairs.
 *
 * Slot key format: "page|section|kind|index" where kind is h2, h3, p, cta,
 * label or li. Keeping the page + section path makes pairing stable as
 * long as the uploaded document follows the baseline structure.
 *
 * @param string $md Markdown source.
 * @return array<string,string>
 */
function lienzo_arc_parse_copy_md( $md ) {
	$out     = array();
	$page    = '';
	$section = '';
	$count   = array();
	$para    = '';

	$flush = function () use ( &$para, &$out, &$count, &$page, &$section ) {
		$text = trim( $para );
		$para = '';
		if ( '' === $text || '' === $page ) {
			return;
		}
		$i            = isset( $count['p'] ) ? $count['p'] + 1 : 0;
		$count['p']   = $i;
		$out[ $page . '|' . $section . '|p|' . $i ] = $text;
	};

	foreach ( preg_split( '/\r?\n/', (string) $md ) as $raw ) {
		$line = trim( $raw );
		if ( '' === $line || preg_match( '/^-{3,}$/', $line ) ) {
			$flush();
			continue;
		}

		if ( preg_match( '/^#\s+(.+)/', $line, $m ) ) {
			$flush();
			// "# 1. HOME" -> "home" — skip the doc title ("Website Copy").
			$label = trim( $m[1] );
			if ( false !== stripos( $label, 'website copy' ) ) {
				continue;
			}
			$page    = sanitize_title( preg_replace( '/^\d+\.\s*/', '', $label ) );
			$section = '';
			$count   = array();
			continue;
		}

		$kind = null;
		if ( preg_match( '/^##\s+(.+)/', $line, $m ) ) {
			$section = sanitize_title( $m[1] );
			$kind    = 'h2';
		} elseif ( preg_match( '/^###\s+(.+)/', $line, $m ) ) {
			$kind = 'h3';
		} elseif ( preg_match( '/^\*\*([^*]+):\*\*\s*(.+)?$/', $line, $m ) ) {
			// "**CTA:** Build Your Team" / "**Primary CTA:** …"
			$kind = 'cta';
			$m[1] = isset( $m[2] ) ? $m[2] : '';
		} elseif ( preg_match( '/^\*\*(.+)\*\*$/', $line ) ) {
			$flush();
			continue; // Bare bold labels (form field names) — not page copy.
		} elseif ( preg_match( '/^-\s+(.+)/', $line, $m ) ) {
			$kind = 'li';
		} else {
			// Continuation of the current paragraph — wrapped md lines
			// merge so re-wrapping the doc never shifts slot indices.
			$line  = rtrim( $line, "\\ \t" );
			$para .= ( '' === $para ? '' : ' ' ) . $line;
			continue;
		}

		$flush();
		$text = trim( preg_replace( '/\*\*/', '', $m[1] ) );
		$text = rtrim( $text, "\\ \t" );
		if ( '' === $text || '' === $page ) {
			continue;
		}

		$i              = isset( $count[ $kind ] ) ? $count[ $kind ] + 1 : 0;
		$count[ $kind ] = $i;
		$out[ $page . '|' . $section . '|' . $kind . '|' . $i ] = $text;
	}
	$flush();

	return $out;
}

/**
 * Builds baseline-text => new-text pairs from a copy markdown document.
 *
 * @param string $md Markdown source (empty returns no pairs).
 * @return array<string,string>
 */
function lienzo_arc_pairs_from_md( $md ) {
	$pairs = array();
	if ( '' === trim( (string) $md ) ) {
		return $pairs;
	}

	$baseline = LIENZOASTRA_DIR . '/assets/arc-copy-baseline.md';
	if ( ! file_exists( $baseline ) ) {
		return $pairs;
	}

	$base = lienzo_arc_parse_copy_md( (string) file_get_contents( $baseline ) );
	$new  = lienzo_arc_parse_copy_md( $md );

	foreach ( $new as $key => $text ) {
		$old = isset( $base[ $key ] ) ? $base[ $key ] : '';
		// Too-short strings risk hitting unrelated fragments — skip them.
		if ( '' !== $old && $old !== $text && strlen( $old ) >= 4 ) {
			$pairs[ $old ] = $text;
		}
	}
	return $pairs;
}

/**
 * Builds baseline-text => new-text pairs from the Customizer setting.
 * Result is cached per request.
 *
 * @return array<string,string>
 */
function lienzo_arc_copy_pairs() {
	static $pairs = null;
	if ( null === $pairs ) {
		$pairs = lienzo_arc_pairs_from_md( (string) lienzoastra_get_option( 'arc_tpl_copy_md' ) );
	}
	return $pairs;
}

/**
 * Replaces copy inside text nodes only — attributes, tags, scripts and
 * styles are preserved untouched.
 *
 * @param string $html   HTML fragment.
 * @param array  $pairs  old => new map.
 * @return string
 */
function lienzo_arc_replace_in_html( $html, $pairs ) {
	if ( '' === trim( $html ) || ! $pairs ) {
		return $html;
	}
	if ( ! class_exists( 'DOMDocument' ) ) {
		return strtr( $html, $pairs ); // phpcs:ignore -- last-resort fallback.
	}

	// Longest keys first so multi-word phrases win over partial overlaps.
	uksort(
		$pairs,
		function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);

	$doc = new DOMDocument();
	// The XML preamble keeps UTF-8 punctuation (—, ·) intact on load.
	@$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ); // phpcs:ignore
	foreach ( $doc->childNodes as $n ) {
		if ( XML_PI_NODE === $n->nodeType ) {
			$doc->removeChild( $n );
			break;
		}
	}

	$xp = new DOMXPath( $doc );

	// Pass 1: matches fully inside a single text node.
	foreach ( $xp->query( '//text()[not(ancestor::script) and not(ancestor::style)]' ) as $node ) {
		$node->nodeValue = strtr( $node->nodeValue, $pairs );
	}

	// Pass 2: phrases split across sibling nodes (styled <span> words etc.).
	// Copy-bearing elements only; the combined node text is matched with
	// whitespace-flexible patterns, then the span is spliced back into the
	// covered nodes — first node takes the new text, the rest are trimmed.
	$targets = $xp->query( '//h1|//h2|//h3|//h4|//h5|//h6|//p|//a|//li|//button|//summary|//figcaption|//blockquote' );
	foreach ( $targets as $el ) {
		$nodes = array();
		foreach ( $xp->query( './/text()[not(ancestor::script) and not(ancestor::style)]', $el ) as $t ) {
			$nodes[] = $t;
		}
		if ( ! $nodes ) {
			continue;
		}
		foreach ( $pairs as $old => $new ) {
			if ( false !== strpos( $new, $old ) ) {
				continue; // new text contains the old one — would loop forever.
			}
			$pattern = '/' . preg_replace( '/\s+/u', '\\s+', preg_quote( $old, '/' ) ) . '/u';
			for ( $guard = 0; $guard < 5; $guard++ ) {
				$combined = '';
				$offsets  = array();
				foreach ( $nodes as $idx => $t ) {
					$offsets[ $idx ] = strlen( $combined );
					$combined       .= $t->nodeValue;
				}
				if ( ! preg_match( $pattern, $combined, $mm, PREG_OFFSET_CAPTURE ) ) {
					break;
				}
				$pos = $mm[0][1];
				$end = $pos + strlen( $mm[0][0] );

				$first = true;
				foreach ( $nodes as $idx => $t ) {
					$ns = $offsets[ $idx ];
					$ne = $ns + strlen( $t->nodeValue );
					if ( $ne <= $pos || $ns >= $end ) {
						continue;
					}
					$ls = max( 0, $pos - $ns );
					$le = min( strlen( $t->nodeValue ), $end - $ns );
					if ( $first ) {
						$suffix        = $ne > $end ? substr( $t->nodeValue, $le ) : '';
						$t->nodeValue  = substr( $t->nodeValue, 0, $ls ) . $new . $suffix;
						$first         = false;
					} else {
						$t->nodeValue = $ne > $end ? substr( $t->nodeValue, $le ) : substr( $t->nodeValue, 0, $ls );
					}
				}
			}
		}
	}

	return $doc->saveHTML();
}

/**
 * Applies the uploaded copy document to imported ARC template pages.
 *
 * @param string $content Post content.
 * @return string
 */
function lienzo_arc_copy_filter( $content ) {
	if ( is_admin() || ! function_exists( 'lienzo_is_arc_template_page' ) || ! lienzo_is_arc_template_page() ) {
		return $content;
	}
	$pairs = lienzo_arc_copy_pairs();
	return $pairs ? lienzo_arc_replace_in_html( $content, $pairs ) : $content;
}
add_filter( 'the_content', 'lienzo_arc_copy_filter', 99 );

/**
 * Swaps baseline copy in the meta/OG description too — it is built from
 * post_excerpt, which lives outside the_content.
 *
 * @param string $description Social/meta description.
 * @return string
 */
function lienzo_arc_copy_meta_filter( $description ) {
	if ( is_admin() || ! function_exists( 'lienzo_is_arc_template_page' ) || ! lienzo_is_arc_template_page() ) {
		return $description;
	}
	$pairs = lienzo_arc_copy_pairs();
	return $pairs ? strtr( $description, $pairs ) : $description;
}
add_filter( 'lienzo_social_description', 'lienzo_arc_copy_meta_filter' );

/**
 * Option name where the pairs currently applied to post content are kept —
 * needed to revert them before applying a different document.
 */
define( 'LIENZO_ARC_APPLIED_PAIRS', 'lienzo_arc_applied_pairs' );

/**
 * Page IDs carrying an imported ARC template: the plugin's page map plus
 * any page flagged with its `_arc_st_slug` meta.
 *
 * @return array<int,int>
 */
function lienzo_arc_target_page_ids() {
	$ids = array();
	foreach ( (array) get_option( 'arc_st_page_map', array() ) as $page_id ) {
		if ( $page_id ) {
			$ids[] = (int) $page_id;
		}
	}
	$found = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'meta_key'       => '_arc_st_slug', // phpcs:ignore -- exact key, not a search.
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	return array_values( array_unique( array_merge( $ids, array_map( 'intval', $found ) ) ) );
}

/**
 * Whether any pair key appears inside a string — cheap pre-check before
 * running the DOM replace on every Elementor value.
 *
 * @param string              $haystack String to probe.
 * @param array<string,string> $pairs    Replacement map.
 * @return bool
 */
function lienzo_arc_pairs_touch( $haystack, $pairs ) {
	foreach ( $pairs as $old => $new ) {
		if ( false !== strpos( $haystack, $old ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Rewrites copy inside a decoded Elementor data tree (array of elements).
 * String values holding a matched baseline text get the HTML-aware replace;
 * everything else is left untouched.
 *
 * @param mixed                $node      Elementor node by reference.
 * @param array<string,string> $pair_sets Sequential replacement maps.
 * @return void
 */
function lienzo_arc_replace_in_elementor_node( &$node, $pair_sets ) {
	if ( is_array( $node ) ) {
		foreach ( $node as &$child ) {
			lienzo_arc_replace_in_elementor_node( $child, $pair_sets );
		}
		return;
	}
	if ( ! is_string( $node ) || strlen( $node ) < 4 ) {
		return;
	}
	foreach ( $pair_sets as $pairs ) {
		if ( $pairs && lienzo_arc_pairs_touch( $node, $pairs ) ) {
			$node = lienzo_arc_replace_in_html( $node, $pairs );
		}
	}
}

/**
 * Applies (and reverts) copy pairs on the imported ARC pages — post_content,
 * post_excerpt and _elementor_data — so the editor shows the new copy too.
 *
 * @param array<string,string> $new_pairs baseline => new text to apply.
 * @param array<string,string> $old_pairs baseline => text previously applied
 *                                        (reverted first so chained uploads work).
 * @param int                  $only_id   Restrict to a single page ID (0 = all).
 * @return int Number of pages updated.
 */
function lienzo_arc_apply_copy_to_posts( $new_pairs, $old_pairs = array(), $only_id = 0 ) {
	$revert = array();
	foreach ( $old_pairs as $old => $new ) {
		$revert[ $new ] = $old;
	}
	$sets = array_filter( array( $revert, $new_pairs ) );
	if ( ! $sets ) {
		return 0;
	}

	$ids = $only_id ? array( (int) $only_id ) : lienzo_arc_target_page_ids();

	$updated = 0;
	foreach ( $ids as $page_id ) {
		$post = get_post( $page_id );
		if ( ! $post ) {
			continue;
		}

		$content = (string) $post->post_content;
		$excerpt = (string) $post->post_excerpt;
		foreach ( $sets as $pairs ) {
			$content = lienzo_arc_replace_in_html( $content, $pairs );
			$excerpt = strtr( $excerpt, $pairs );
		}

		$changed = $content !== $post->post_content || $excerpt !== $post->post_excerpt;
		if ( $changed ) {
			// Imported markup intentionally carries <style>/<form>/inline
			// handlers — KSES would strip them without a privileged context.
			kses_remove_filters();
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
				)
			);
			kses_init_filters();
		}

		$elementor = get_post_meta( $page_id, '_elementor_data', true );
		if ( is_string( $elementor ) && '' !== $elementor ) {
			$data = json_decode( $elementor, true );
			if ( is_array( $data ) ) {
				lienzo_arc_replace_in_elementor_node( $data, $sets );
				update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			}
		}

		if ( $changed ) {
			$updated++;
		}
	}
	return $updated;
}

/**
 * Persists the copy document into the imported pages when the Customizer is
 * saved. Previously applied pairs are reverted first, so uploading another
 * document (or clearing the field) returns the content to baseline before
 * the new copy goes in.
 *
 * @return void
 */
function lienzo_arc_apply_copy_on_save() {
	$new_pairs = lienzo_arc_pairs_from_md( (string) get_theme_mod( 'lienzoastra_arc_tpl_copy_md', '' ) );
	$old_pairs = (array) get_option( LIENZO_ARC_APPLIED_PAIRS, array() );
	if ( ! $new_pairs && ! $old_pairs ) {
		return;
	}
	lienzo_arc_apply_copy_to_posts( $new_pairs, $old_pairs );
	if ( $new_pairs ) {
		update_option( LIENZO_ARC_APPLIED_PAIRS, $new_pairs, false );
	} else {
		delete_option( LIENZO_ARC_APPLIED_PAIRS );
	}
}
add_action( 'customize_save_after', 'lienzo_arc_apply_copy_on_save' );

/**
 * Applies the stored copy document to a page right after the importer flags
 * it (`_arc_st_slug` meta is written once the content is in place), so pages
 * imported after the document was saved get the copy immediately.
 *
 * @param int    $mid        Meta row ID.
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Meta key.
 * @return void
 */
function lienzo_arc_apply_copy_on_import( $mid, $object_id, $meta_key ) {
	if ( '_arc_st_slug' !== $meta_key ) {
		return;
	}
	$pairs = lienzo_arc_pairs_from_md( (string) get_theme_mod( 'lienzoastra_arc_tpl_copy_md', '' ) );
	if ( $pairs ) {
		lienzo_arc_apply_copy_to_posts( $pairs, array(), (int) $object_id );
	}
}
add_action( 'added_post_meta', 'lienzo_arc_apply_copy_on_import', 10, 3 );
add_action( 'updated_post_meta', 'lienzo_arc_apply_copy_on_import', 10, 3 );

/**
 * Palette presets for imported ARC templates. Each preset maps the CSS
 * tokens every kit consumes (plus the arc-site leaf/gold aliases) and the
 * CTA button colors.
 *
 * @return array<string,array<string,string>>
 */
function lienzo_arc_palette_presets() {
	return array(
		'arc'       => array(
			'brand' => '#6FAF67', 'brand-dark' => '#438F69', 'accent' => '#58B8A9',
			'navy'  => '#0F1C2E', 'mint'       => '#F5F8F4', 'cream'  => '#EAF1E8',
			'leaf'  => '#A8C85A', 'gold'       => '#D7C94F',
			'cta_bg' => '#A8C85A', 'cta_text'  => '#0F1C2E',
		),
		'corporate' => array(
			'brand' => '#2563EB', 'brand-dark' => '#1D4ED8', 'accent' => '#38BDF8',
			'navy'  => '#0B1526', 'mint'       => '#F4F7FB', 'cream'  => '#E2E8F0',
			'leaf'  => '#60A5FA', 'gold'       => '#F59E0B',
			'cta_bg' => '#2563EB', 'cta_text'  => '#FFFFFF',
		),
		'ocean'     => array(
			'brand' => '#0E7490', 'brand-dark' => '#155E75', 'accent' => '#14B8A6',
			'navy'  => '#06232E', 'mint'       => '#F0FAF9', 'cream'  => '#DFF3F0',
			'leaf'  => '#2DD4BF', 'gold'       => '#FBBF24',
			'cta_bg' => '#14B8A6', 'cta_text'  => '#06232E',
		),
		'sunset'    => array(
			'brand' => '#EA580C', 'brand-dark' => '#C2410C', 'accent' => '#F59E0B',
			'navy'  => '#2A211A', 'mint'       => '#FBF7F2', 'cream'  => '#F6EDE3',
			'leaf'  => '#FB923C', 'gold'       => '#EAB308',
			'cta_bg' => '#F59E0B', 'cta_text'  => '#2A211A',
		),
		'mono'      => array(
			'brand' => '#475569', 'brand-dark' => '#334155', 'accent' => '#64748B',
			'navy'  => '#0F172A', 'mint'       => '#F8FAFC', 'cream'  => '#F1F5F9',
			'leaf'  => '#94A3B8', 'gold'       => '#CBD5E1',
			'cta_bg' => '#0F172A', 'cta_text'  => '#FFFFFF',
		),
	);
}

/**
 * Resolves the palette to apply on imported templates: a preset, or the
 * individual color controls when the choice is "custom". Empty result means
 * the template keeps its bundled palette.
 *
 * @return array<string,string> token => css value (includes cta_bg/cta_text).
 */
function lienzo_arc_palette_colors() {
	$choice = lienzoastra_get_option( 'arc_tpl_palette' );
	if ( '' === $choice || 'default' === $choice ) {
		return array();
	}

	$presets = lienzo_arc_palette_presets();
	if ( isset( $presets[ $choice ] ) ) {
		$out = array();
		foreach ( $presets[ $choice ] as $token => $hex ) {
			$out[ str_replace( '_', '-', $token ) ] = $hex;
		}
		return $out;
	}

	if ( 'custom' === $choice ) {
		$out = array();
		foreach ( array( 'brand', 'brand_dark', 'accent', 'navy', 'mint', 'cream', 'cta_bg', 'cta_text' ) as $key ) {
			$v = sanitize_hex_color( (string) lienzoastra_get_option( 'arc_tpl_color_' . $key ) );
			if ( $v ) {
				$out[ str_replace( '_', '-', $key ) ] = $v;
			}
		}
		return $out;
	}

	return array();
}

/**
 * CSS fragment overriding the template's color tokens + primary CTA on the
 * arc-tpl wrapper — printed from chrome_css so it reaches imported pages.
 *
 * @return string
 */
function lienzo_arc_palette_css() {
	$colors = lienzo_arc_palette_colors();
	if ( ! $colors ) {
		return '';
	}

	$css = 'body.arc-tpl, .arc-tpl {';
	foreach ( $colors as $token => $hex ) {
		if ( 'cta-bg' === $token || 'cta-text' === $token ) {
			continue;
		}
		$css .= '--color-' . $token . ':' . $hex . ';';
	}
	$css .= '}';

	if ( ! empty( $colors['cta-bg'] ) ) {
		$css .= 'body.arc-tpl .btn-primary{background:' . $colors['cta-bg'] . ';';
		if ( ! empty( $colors['cta-text'] ) ) {
			$css .= 'color:' . $colors['cta-text'] . ';';
		}
		$css .= '}';
	}
	return $css;
}
