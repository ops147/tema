<?php
/**
 * Elementor converter — turns a template's <body> markup into the
 * _elementor_data element tree (containers + native widgets).
 *
 * Strategy: the compiled Tailwind stylesheet ships with the plugin, so every
 * element keeps its original utility classes via Elementor's "CSS Classes"
 * field. The DOM is mapped onto editable widgets:
 *   section/div/header/footer/nav → container (html_tag preserved)
 *   h1–h6                          → heading widget
 *   p / ul / ol / leaf divs        → text-editor widget
 *   img                            → image widget (Media Library attachment)
 *   a (buttons, nav links…)        → text-editor widget (anchor kept editable)
 *   svg / form / iframe / table    → html widget
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds Elementor page data from template markup.
 */
final class Arc_ST_Elementor {

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
	 * Label carried by the last <!-- ===== X ===== --> comment.
	 *
	 * @var string
	 */
	private static $pending_title = '';

	/**
	 * Tags that become Elementor containers (tag => html_tag setting).
	 *
	 * @var array
	 */
	const CONTAINER_TAGS = array(
		'div'     => 'div',
		'section' => 'section',
		'header'  => 'header',
		'footer'  => 'footer',
		'nav'     => 'nav',
		'main'    => 'main',
		'article' => 'article',
		'aside'   => 'aside',
		'figure'  => 'figure',
	);

	/**
	 * Inline-level tags that may live inside a text-editor widget.
	 *
	 * @var array
	 */
	const PHRASING_TAGS = array(
		'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn',
		'em', 'i', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong',
		'sub', 'sup', 'svg', 'time', 'u', 'var', 'wbr',
	);

	/**
	 * Tags rendered verbatim via the HTML widget.
	 *
	 * @var array
	 */
	const RAW_TAGS = array(
		'form', 'input', 'select', 'textarea', 'button', 'iframe', 'script',
		'style', 'table', 'hr', 'noscript', 'video', 'audio', 'canvas', 'svg',
	);

	/**
	 * Whether Elementor (with container support) is usable.
	 *
	 * @return bool
	 */
	public static function available() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( 'DOMDocument' ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->experiments ) && method_exists( $plugin->experiments, 'is_feature_active' ) ) {
			return (bool) $plugin->experiments->is_feature_active( 'container' );
		}
		return true;
	}

	/**
	 * Converts a template into a list of top-level Elementor elements.
	 *
	 * @param string $slug      Template slug.
	 * @param array  $media_map filename => array{id:int,url:string}.
	 * @param array  $link_map  template slug => post ID.
	 * @return array Elementor content array.
	 */
	public static function build( $slug, $media_map, $link_map ) {
		self::$media         = (array) $media_map;
		self::$links         = (array) $link_map;
		self::$pending_title = '';

		// Lienzo Astra renders the site chrome from the active theme. Import
		// only the page body there; other themes keep the bundled chrome in the
		// Elementor document as before.
		$body = Arc_ST_Templates::body_html( $slug, ! Arc_ST_Importer::uses_lienzo_chrome() );
		if ( '' === $body ) {
			return array();
		}

		$dom                     = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = false;
		@$dom->loadHTML( // phpcs:ignore WordPress.PHP.NoSilencedErrors
			'<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $body . '</body></html>',
			LIBXML_NOERROR | LIBXML_NOWARNING
		);

		$body_node = $dom->getElementsByTagName( 'body' )->item( 0 );
		return $body_node ? self::children( $dom, $body_node, false ) : array();
	}

	/**
	 * Converts a node's children into Elementor elements.
	 *
	 * @param DOMDocument $dom    Document.
	 * @param DOMNode     $parent Parent node.
	 * @param bool        $inner  Whether results are nested (isInner).
	 * @return array
	 */
	private static function children( DOMDocument $dom, DOMNode $parent, $inner ) {
		$elements = array();
		foreach ( $parent->childNodes as $child ) {
			$el = self::node( $dom, $child, $inner );
			if ( null !== $el ) {
				$elements[] = $el;
			}
		}
		return $elements;
	}

	/**
	 * Converts a single DOM node into one Elementor element (or null).
	 *
	 * @param DOMDocument $dom   Document.
	 * @param DOMNode     $node  Node.
	 * @param bool        $inner Nested flag.
	 * @return array|null
	 */
	private static function node( DOMDocument $dom, DOMNode $node, $inner ) {
		if ( XML_COMMENT_NODE === $node->nodeType ) {
			if ( preg_match( '/=+\s*(.+?)\s*=+/', (string) $node->nodeValue, $m ) ) {
				self::$pending_title = trim( $m[1] );
			}
			return null;
		}

		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( (string) $node->nodeValue );
			return '' === $text ? null : self::widget( 'text-editor', array( 'editor' => esc_html( $text ) ) );
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return null;
		}

		$tag    = strtolower( $node->nodeName );
		$classes = trim( (string) $node->getAttribute( 'class' ) );
		$css_id  = trim( (string) $node->getAttribute( 'id' ) );

		switch ( true ) {
			case preg_match( '/^h[1-6]$/', $tag ):
				return self::widget(
					'heading',
					array(
						'title'       => self::inner( $dom, $node ),
						'header_size' => $tag,
					),
					$classes,
					$css_id
				);

			case 'p' === $tag:
				return self::widget( 'text-editor', array( 'editor' => self::inner( $dom, $node ) ), $classes, $css_id );

			case 'ul' === $tag:
			case 'ol' === $tag:
				// Outer markup carries the classes — do not duplicate on the widget.
				return self::widget( 'text-editor', array( 'editor' => self::outer( $dom, $node ) ) );

			case 'img' === $tag:
				return self::image( $node, $classes, $css_id );

			case 'a' === $tag:
				return self::anchor( $dom, $node, $classes, $css_id );

			case in_array( $tag, self::RAW_TAGS, true ):
				return self::widget( 'html', array( 'html' => self::outer( $dom, $node ) ) );

			case isset( self::CONTAINER_TAGS[ $tag ] ):
				if ( self::is_leaf( $node ) ) {
					return self::widget( 'text-editor', array( 'editor' => self::inner( $dom, $node ) ), $classes, $css_id );
				}
				return self::container( $dom, $node, $tag, $inner );

			default:
				// Unknown tag: treat inline-looking content as text, else raw HTML.
				return self::is_leaf( $node )
					? self::widget( 'text-editor', array( 'editor' => self::inner( $dom, $node ) ), $classes, $css_id )
					: self::widget( 'html', array( 'html' => self::outer( $dom, $node ) ), $classes, $css_id );
		}
	}

	/**
	 * True when the element only holds phrasing content → editable as text.
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
	 * Builds a container element.
	 *
	 * @param DOMDocument $dom   Document.
	 * @param DOMNode     $node  Element node.
	 * @param string      $tag   Tag name.
	 * @param bool        $inner Nested flag.
	 * @return array
	 */
	private static function container( DOMDocument $dom, DOMNode $node, $tag, $inner ) {
		$settings = array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'html_tag'       => self::CONTAINER_TAGS[ $tag ],
		);

		if ( ! $inner ) {
			// Full-bleed recipe: stops Elementor's boxed defaults adding white
			// strips around edge-to-edge sections on Canvas pages.
			$settings['flex_align_items'] = 'stretch';
			$settings['width']            = array(
				'unit' => '%',
				'size' => 100,
			);
			$settings['padding']          = array(
				'unit'     => 'px',
				'top'      => '0',
				'right'    => '0',
				'bottom'   => '0',
				'left'     => '0',
				'isLinked' => true,
			);
			$settings['flex_gap']         = array(
				'unit'   => 'px',
				'size'   => 0,
				'column' => '0',
				'row'    => '0',
			);
		}

		$classes = trim( (string) $node->getAttribute( 'class' ) );
		if ( '' !== $classes ) {
			$settings['css_classes'] = $classes;
		}
		$css_id = trim( (string) $node->getAttribute( 'id' ) );
		if ( '' !== $css_id ) {
			$settings['element_id'] = $css_id;
		}
		if ( ! $inner && '' !== self::$pending_title ) {
			$settings['_title']      = self::$pending_title;
			self::$pending_title     = '';
		}

		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => (bool) $inner,
			'settings' => $settings,
			'elements' => self::children( $dom, $node, true ),
		);
	}

	/**
	 * Converts an anchor. Simple btn-* links become native button widgets
	 * (URL + label editable as fields — the Tailwind classes sit on the
	 * widget wrapper and the bridge CSS neutralizes Elementor's own button
	 * skin). Anything else stays editable rich text.
	 *
	 * @param DOMDocument $dom     Document.
	 * @param DOMNode     $node    Anchor element.
	 * @param string      $classes Element classes.
	 * @param string      $css_id  Element id.
	 * @return array
	 */
	private static function anchor( DOMDocument $dom, DOMNode $node, $classes, $css_id ) {
		if ( preg_match( '/\bbtn-/', $classes ) && self::is_leaf( $node ) ) {
			return self::widget(
				'button',
				array(
					'text' => trim( wp_strip_all_tags( self::inner( $dom, $node ) ) ),
					'link' => array(
						'url'         => self::resolve_href( (string) $node->getAttribute( 'href' ) ),
						'is_external' => '_blank' === (string) $node->getAttribute( 'target' ),
						'nofollow'    => false,
					),
				),
				$classes,
				$css_id
			);
		}
		return self::widget( 'text-editor', array( 'editor' => self::outer( $dom, $node ) ) );
	}

	/**
	 * Resolves a bare href value (template slug links → permalinks).
	 *
	 * @param string $href Raw href attribute.
	 * @return string
	 */
	private static function resolve_href( $href ) {
		if ( preg_match( '/^([\w-]+)\.html$/', trim( $href ), $m )
			&& isset( self::$links[ $m[1] ] )
			&& get_post( self::$links[ $m[1] ] ) ) {
			return (string) get_permalink( self::$links[ $m[1] ] );
		}
		return $href;
	}

	/**
	 * Builds a widget element.
	 *
	 * @param string $type     widgetType.
	 * @param array  $settings Widget settings.
	 * @param string $classes  Extra CSS classes.
	 * @param string $css_id   CSS ID.
	 * @return array
	 */
	private static function widget( $type, $settings, $classes = '', $css_id = '' ) {
		if ( '' !== $classes ) {
			$settings['css_classes'] = $classes;
		}
		if ( '' !== $css_id ) {
			$settings['_element_id'] = $css_id;
		}
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * Builds an image widget backed by a Media Library attachment.
	 *
	 * @param DOMNode $node    img element.
	 * @param string  $classes Extra CSS classes.
	 * @param string  $css_id  CSS ID.
	 * @return array
	 */
	private static function image( DOMNode $node, $classes, $css_id ) {
		$src      = (string) $node->getAttribute( 'src' );
		$filename = basename( rawurldecode( (string) wp_parse_url( $src, PHP_URL_PATH ) ) );
		$media    = isset( self::$media[ $filename ] ) ? self::$media[ $filename ] : null;

		$image = array(
			'url'  => $media ? $media['url'] : ARC_ST_URL . 'assets/img/' . $filename,
			'size' => '',
		);
		if ( $media ) {
			$image['id'] = $media['id'];
		}

		return self::widget(
			'image',
			array(
				'image'       => $image,
				'image_size'  => 'full',
				'align'       => '',
				'caption_source' => 'none',
			),
			$classes,
			$css_id
		);
	}

	/**
	 * Elementor-style element ID (7 hex chars).
	 *
	 * @return string
	 */
	private static function id() {
		return substr( bin2hex( random_bytes( 8 ) ), 0, 7 );
	}

	/**
	 * Inner HTML of a node, with template links resolved.
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
		return self::resolve_links( $html );
	}

	/**
	 * Outer HTML of a node, with template links resolved.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param DOMNode     $node Node.
	 * @return string
	 */
	private static function outer( DOMDocument $dom, DOMNode $node ) {
		return self::resolve_links( (string) $dom->saveHTML( $node ) );
	}

	/**
	 * Rewrites href="<slug>.html" inside markup snippets.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private static function resolve_links( $html ) {
		return Arc_ST_Templates::resolve_page_links( $html, self::$links );
	}
}
