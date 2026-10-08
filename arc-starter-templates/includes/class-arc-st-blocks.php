<?php
/**
 * Blocks converter — turns a template's <body> markup into native Gutenberg
 * block markup, so imported pages are editable in the block editor when
 * Elementor is not installed.
 *
 * Mapping (per the WordPress block serialization spec):
 *   section/div/header/footer/nav/main/article/aside → wp:group (tagName + className)
 *   figure(img+figcaption)                            → wp:image (+ caption)
 *   h1–h6                                             → wp:heading (level + anchor)
 *   p / leaf containers / standalone links            → wp:paragraph (rich text)
 *   ul / ol                                           → wp:list + wp:list-item
 *   blockquote                                        → wp:quote
 *   form/svg/table/iframe/script…                     → wp:html (verbatim)
 *   img (standalone)                                  → wp:image (Media Library id)
 *
 * Tailwind utility classes survive on every element through className, so the
 * bundled stylesheet keeps the design identical — same strategy the Elementor
 * converter uses.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * DOM → serialized block markup converter.
 */
final class Arc_ST_Blocks {

	/**
	 * Media map for the current conversion (filename => {id,url}).
	 *
	 * @var array
	 */
	private static $media = array();

	/**
	 * Link map for the current conversion (template slug => post ID).
	 *
	 * @var array
	 */
	private static $links = array();

	/**
	 * Container tags mapped to wp:group (tag => tagName attribute).
	 *
	 * @var array
	 */
	const GROUP_TAGS = array(
		'div'     => 'div',
		'section' => 'section',
		'header'  => 'header',
		'footer'  => 'footer',
		'nav'     => 'nav',
		'main'    => 'main',
		'article' => 'article',
		'aside'   => 'aside',
	);

	/**
	 * Inline-level tags that may live inside rich-text content.
	 *
	 * @var array
	 */
	const PHRASING_TAGS = array(
		'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn',
		'em', 'i', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong',
		'sub', 'sup', 'svg', 'time', 'u', 'var', 'wbr',
	);

	/**
	 * Tags stored verbatim inside a wp:html block.
	 *
	 * @var array
	 */
	const RAW_TAGS = array(
		'form', 'input', 'select', 'textarea', 'button', 'iframe', 'script',
		'style', 'table', 'hr', 'noscript', 'video', 'audio', 'canvas',
		'picture', 'source', 'object', 'embed', 'template', 'fieldset',
		'details', 'summary', 'map', 'area',
	);

	/**
	 * Converts a template into serialized block markup.
	 *
	 * @param string $slug      Template slug.
	 * @param array  $media_map filename => array{id:int,url:string}.
	 * @param array  $link_map  template slug => post ID.
	 * @return string Block markup ('' when the template/DOM is unavailable).
	 */
	public static function build( $slug, $media_map, $link_map ) {
		self::$media = (array) $media_map;
		self::$links = (array) $link_map;

		$body = Arc_ST_Templates::body_html( $slug, false );
		if ( '' === $body || ! class_exists( 'DOMDocument' ) ) {
			return '';
		}

		$dom                     = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = false;
		@$dom->loadHTML( // phpcs:ignore WordPress.PHP.NoSilencedErrors
			'<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $body . '</body></html>',
			LIBXML_NOERROR | LIBXML_NOWARNING
		);

		$body_node = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body_node ) {
			return '';
		}

		$markup = '';
		foreach ( $body_node->childNodes as $child ) {
			$markup .= self::node( $dom, $child );
		}
		return $markup;
	}

	/**
	 * Converts a single DOM node into block markup.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param DOMNode     $node Node.
	 * @return string
	 */
	private static function node( DOMDocument $dom, DOMNode $node ) {
		if ( XML_COMMENT_NODE === $node->nodeType ) {
			return '';
		}

		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( (string) $node->nodeValue );
			return '' === $text ? '' : self::paragraph( esc_html( $text ) );
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag     = strtolower( $node->nodeName );
		$classes = trim( (string) $node->getAttribute( 'class' ) );
		$css_id  = trim( (string) $node->getAttribute( 'id' ) );

		switch ( true ) {
			case (bool) preg_match( '/^h[1-6]$/', $tag ):
				return self::heading( (int) substr( $tag, 1 ), self::inner( $dom, $node ), $classes, $css_id );

			case 'p' === $tag:
				return self::paragraph( self::inner( $dom, $node ), $classes );

			case 'ul' === $tag:
			case 'ol' === $tag:
				return self::list_block( $dom, $node, 'ol' === $tag, $classes );

			case 'blockquote' === $tag:
				return self::quote( self::inner( $dom, $node ), $classes );

			case 'img' === $tag:
				return self::image( $node, null, $classes );

			case 'a' === $tag:
				// Standalone links become editable rich text inside a paragraph;
				// link-cards (anchors wrapping blocks) stay verbatim HTML.
				return self::is_leaf( $node )
					? self::paragraph( self::outer( $dom, $node ) )
					: self::html( self::outer( $dom, $node ) );

			case in_array( $tag, self::RAW_TAGS, true ):
				return self::html( self::outer( $dom, $node ) );

			case 'figure' === $tag:
				$img = self::figure_image( $node );
				if ( $img ) {
					return self::image( $img, self::figure_caption( $dom, $node ), $classes );
				}
				return self::group( $dom, $node, 'figure', $classes, $css_id );

			case isset( self::GROUP_TAGS[ $tag ] ):
				if ( self::is_leaf( $node ) ) {
					return self::paragraph( self::inner( $dom, $node ), $classes );
				}
				return self::group( $dom, $node, self::GROUP_TAGS[ $tag ], $classes, $css_id );

			default:
				return self::is_leaf( $node )
					? self::paragraph( self::inner( $dom, $node ), $classes )
					: self::html( self::outer( $dom, $node ) );
		}
	}

	/**
	 * True when the element only holds phrasing content → editable rich text.
	 *
	 * @param DOMNode $node Element.
	 * @return bool
	 */
	private static function is_leaf( DOMNode $node ) {
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType
				&& ! in_array( strtolower( $child->nodeName ), self::PHRASING_TAGS, true ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the lone <img> of a figure that only wraps an image (+caption).
	 *
	 * @param DOMNode $figure figure element.
	 * @return DOMNode|null
	 */
	private static function figure_image( DOMNode $figure ) {
		$img = null;
		foreach ( $figure->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			$tag = strtolower( $child->nodeName );
			if ( 'img' === $tag ) {
				if ( $img ) {
					return null;
				}
				$img = $child;
			} elseif ( 'figcaption' !== $tag ) {
				return null;
			}
		}
		return $img;
	}

	/**
	 * Inner HTML of a figure's figcaption, or ''.
	 *
	 * @param DOMDocument $dom    Document.
	 * @param DOMNode     $figure figure element.
	 * @return string
	 */
	private static function figure_caption( DOMDocument $dom, DOMNode $figure ) {
		foreach ( $figure->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType
				&& 'figcaption' === strtolower( $child->nodeName ) ) {
				return self::inner( $dom, $child );
			}
		}
		return '';
	}

	/* ---------------------------------------------------------------------
	 * Block emitters
	 * ------------------------------------------------------------------- */

	/**
	 * Opening block comment with optional attribute JSON.
	 *
	 * @param string $name  Block name (no core/ prefix).
	 * @param array  $attrs Block attributes.
	 * @return string
	 */
	private static function open( $name, $attrs = array() ) {
		$json = $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) : '';
		return "<!-- wp:{$name}{$json} -->\n";
	}

	/**
	 * Closing block comment.
	 *
	 * @param string $name Block name.
	 * @return string
	 */
	private static function close( $name ) {
		return "<!-- /wp:{$name} -->\n";
	}

	/**
	 * Class attribute pair for serialized markup + block JSON.
	 *
	 * @param string $classes Tailwind classes ('' when none).
	 * @param array  $attrs   Attributes being built (className is appended).
	 * @return string ' class="…"' fragment or ''.
	 */
	private static function cls( $classes, &$attrs ) {
		if ( '' === $classes ) {
			return '';
		}
		$attrs['className'] = $classes;
		return ' class="' . esc_attr( $classes ) . '"';
	}

	/**
	 * <!-- wp:paragraph --><p>rich text</p><!-- /wp:paragraph -->
	 *
	 * @param string $inner   Inner HTML.
	 * @param string $classes Extra classes.
	 * @return string
	 */
	private static function paragraph( $inner, $classes = '' ) {
		$inner = trim( (string) $inner );
		if ( '' === $inner || '<br>' === $inner || '<br/>' === $inner ) {
			return '';
		}
		$attrs = array();
		$cls   = self::cls( $classes, $attrs );
		return self::open( 'paragraph', $attrs ) . '<p' . $cls . '>' . $inner . '</p>' . self::close( 'paragraph' );
	}

	/**
	 * <!-- wp:heading --><hN>rich text</hN><!-- /wp:heading -->
	 *
	 * @param int    $level   1–6.
	 * @param string $inner   Inner HTML.
	 * @param string $classes Extra classes.
	 * @param string $css_id  Element id → anchor attribute.
	 * @return string
	 */
	private static function heading( $level, $inner, $classes, $css_id ) {
		$attrs = array();
		if ( 2 !== $level ) {
			$attrs['level'] = $level;
		}
		if ( '' !== $css_id ) {
			$attrs['anchor'] = $css_id;
		}
		$cls = self::cls( $classes, $attrs );
		$id  = '' !== $css_id ? ' id="' . esc_attr( $css_id ) . '"' : '';
		$tag = 'h' . $level;
		return self::open( 'heading', $attrs ) . '<' . $tag . $cls . $id . '>' . $inner . '</' . $tag . '>' . self::close( 'heading' );
	}

	/**
	 * <!-- wp:list --><ul><li>…</li></ul><!-- /wp:list --> — items become
	 * wp:list-item children (current core list format).
	 *
	 * @param DOMDocument $dom     Document.
	 * @param DOMNode     $node    ul/ol element.
	 * @param bool        $ordered Ordered list.
	 * @param string      $classes Extra classes.
	 * @return string
	 */
	private static function list_block( DOMDocument $dom, DOMNode $node, $ordered, $classes ) {
		$attrs = array();
		if ( $ordered ) {
			$attrs['ordered'] = true;
		}
		$cls  = self::cls( $classes, $attrs );
		$tag  = $ordered ? 'ol' : 'ul';
		$out  = self::open( 'list', $attrs ) . '<' . $tag . $cls . '>';
		$used = false;
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType && 'li' === strtolower( $child->nodeName ) ) {
				$out  .= "<!-- wp:list-item -->\n<li>" . self::inner( $dom, $child ) . "</li>\n<!-- /wp:list-item -->\n";
				$used  = true;
			} else {
				$out .= self::node( $dom, $child );
			}
		}
		$out .= '</' . $tag . '>' . self::close( 'list' );
		// Lists without <li> children stay verbatim — never lose content.
		return $used ? $out : self::html( self::outer( $dom, $node ) );
	}

	/**
	 * <!-- wp:quote --><blockquote class="wp-block-quote">…</blockquote><!-- /wp:quote -->
	 *
	 * @param string $inner   Inner HTML.
	 * @param string $classes Extra classes.
	 * @return string
	 */
	private static function quote( $inner, $classes ) {
		$attrs = array();
		self::cls( $classes, $attrs );
		$cls = 'wp-block-quote' . ( '' !== $classes ? ' ' . esc_attr( $classes ) : '' );
		return self::open( 'quote', $attrs ) . '<blockquote class="' . $cls . '">' . $inner . '</blockquote>' . self::close( 'quote' );
	}

	/**
	 * <!-- wp:image --><figure class="wp-block-image size-full"><img/></figure><!-- /wp:image -->
	 * Uses the Media Library attachment id when the image was imported.
	 *
	 * @param DOMNode     $img         img element.
	 * @param string|null $caption     Figcaption inner HTML, or null.
	 * @param string      $classes     Extra classes.
	 * @return string
	 */
	private static function image( DOMNode $img, $caption, $classes ) {
		$src      = (string) $img->getAttribute( 'src' );
		$alt      = (string) $img->getAttribute( 'alt' );
		$filename = basename( rawurldecode( (string) wp_parse_url( $src, PHP_URL_PATH ) ) );
		$media    = isset( self::$media[ $filename ] ) ? self::$media[ $filename ] : null;

		$attrs = array(
			'sizeSlug'         => 'full',
			'linkDestination'  => 'none',
		);
		if ( $media ) {
			$attrs['id'] = (int) $media['id'];
		}
		if ( '' !== $classes ) {
			$attrs['className'] = $classes;
		}

		$url = $media ? $media['url'] : ARC_ST_URL . 'assets/img/' . $filename;
		$cls = 'wp-block-image size-full' . ( '' !== $classes ? ' ' . esc_attr( $classes ) : '' );

		$fig = '<figure class="' . $cls . '"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"/>';
		if ( null !== $caption && '' !== trim( $caption ) ) {
			$fig .= '<figcaption>' . $caption . '</figcaption>';
		}
		$fig .= '</figure>';

		return self::open( 'image', $attrs ) . $fig . self::close( 'image' );
	}

	/**
	 * <!-- wp:group --><TagName class="wp-block-group …">inner blocks</TagName><!-- /wp:group -->
	 *
	 * @param DOMDocument $dom     Document.
	 * @param DOMNode     $node    Container element.
	 * @param string      $tag     tagName attribute value.
	 * @param string      $classes Extra classes.
	 * @param string      $css_id  Element id → anchor attribute.
	 * @return string
	 */
	private static function group( DOMDocument $dom, DOMNode $node, $tag, $classes, $css_id ) {
		$inner = '';
		foreach ( $node->childNodes as $child ) {
			$inner .= self::node( $dom, $child );
		}
		if ( '' === trim( $inner ) ) {
			return '';
		}

		$attrs = array( 'tagName' => $tag );
		if ( '' !== $css_id ) {
			$attrs['anchor'] = $css_id;
		}
		if ( '' !== $classes ) {
			$attrs['className'] = $classes;
		}

		$cls = 'wp-block-group' . ( '' !== $classes ? ' ' . esc_attr( $classes ) : '' );
		$id  = '' !== $css_id ? ' id="' . esc_attr( $css_id ) . '"' : '';

		return self::open( 'group', $attrs ) . '<' . $tag . ' class="' . $cls . '"' . $id . '>' . $inner . '</' . $tag . '>' . self::close( 'group' );
	}

	/**
	 * <!-- wp:html -->raw markup<!-- /wp:html -->
	 *
	 * @param string $raw Verbatim HTML.
	 * @return string
	 */
	private static function html( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		return "<!-- wp:html -->\n" . $raw . "\n<!-- /wp:html -->\n";
	}

	/* ---------------------------------------------------------------------
	 * Markup helpers (links + media resolution, comment stripping)
	 * ------------------------------------------------------------------- */

	/**
	 * Inner HTML of a node with links/media resolved and comments stripped.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param DOMNode     $node Node.
	 * @return string
	 */
	private static function inner( DOMDocument $dom, DOMNode $node ) {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
		return self::clean( $html );
	}

	/**
	 * Outer HTML of a node with links/media resolved and comments stripped.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param DOMNode     $node Node.
	 * @return string
	 */
	private static function outer( DOMDocument $dom, DOMNode $node ) {
		return self::clean( (string) $dom->saveHTML( $node ) );
	}

	/**
	 * Normalizes extracted markup: strips HTML comments (they would confuse
	 * block delimiters), rewrites <slug>.html links to permalinks and Img/
	 * paths to Media Library URLs.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function clean( $html ) {
		$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
		$html = Arc_ST_Templates::resolve_page_links( $html, self::$links );
		$html = Arc_ST_Templates::assets_to_media_urls( $html, self::$media );
		return $html;
	}
}
