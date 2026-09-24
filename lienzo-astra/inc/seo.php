<?php
/**
 * Lightweight SEO helpers: canonical URLs, Open Graph / Twitter Card meta
 * tags, robots directives and a JSON-LD graph (Organization, WebSite with
 * SearchAction, Article and BreadcrumbList).
 *
 * Everything here backs off automatically when a dedicated SEO plugin
 * (Yoast, Rank Math, SEOPress, All in One SEO, The SEO Framework) is active,
 * so Lienzo never fights another plugin for the same tags.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_has_seo_plugin' ) ) {
	/**
	 * Detect whether a full SEO plugin is already handling social/meta tags.
	 *
	 * @return bool
	 */
	function lienzo_has_seo_plugin() {
		$has_plugin = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'THE_SEO_FRAMEWORK_VERSION' )
			|| class_exists( 'SEOPress' )
			|| class_exists( 'AIOSEO\\Plugin\\AIOSEO' );

		return (bool) apply_filters( 'lienzo_has_seo_plugin', $has_plugin );
	}
}

if ( ! function_exists( 'lienzo_seo_enabled' ) ) {
	/**
	 * Whether Lienzo should print its own SEO meta tags.
	 *
	 * @return bool
	 */
	function lienzo_seo_enabled() {
		if ( lienzo_has_seo_plugin() ) {
			return false;
		}

		return (bool) apply_filters( 'lienzo_seo_meta_tags', true );
	}
}

if ( ! function_exists( 'lienzo_get_social_image_data' ) ) {
	/**
	 * Best available social-share image for the current view: featured
	 * image, else the first image inside the content, else the site icon.
	 *
	 * @return array{url:string,width:int,height:int,alt:string} Empty array when nothing is found.
	 */
	function lienzo_get_social_image_data() {
		$image_id = 0;

		if ( is_singular() ) {
			if ( has_post_thumbnail() ) {
				$image_id = get_post_thumbnail_id();
			} elseif ( apply_filters( 'lienzo_social_image_content_fallback', true ) ) {
				// First <img> in the content as a fallback (unknown size).
				$content = (string) get_post_field( 'post_content', get_the_ID() );
				if ( preg_match( '#<img[^>]+src=["\']([^"\']+)["\']#i', $content, $match ) ) {
					return [
						'url'    => $match[1],
						'width'  => 0,
						'height' => 0,
						'alt'    => get_the_title(),
					];
				}
			}
		} elseif ( has_site_icon() ) {
			$image_id = (int) get_option( 'site_icon' );
		}

		if ( ! $image_id && has_site_icon() ) {
			$image_id = (int) get_option( 'site_icon' );
		}

		if ( ! $image_id ) {
			return [];
		}

		$image = wp_get_attachment_image_src( $image_id, 'large' );

		if ( ! $image ) {
			return [];
		}

		$alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		return [
			'url'    => $image[0],
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
			'alt'    => '' !== $alt ? $alt : get_bloginfo( 'name' ),
		];
	}
}

if ( ! function_exists( 'lienzo_get_social_image' ) ) {
	/**
	 * Social-share image URL only (kept for backwards compatibility).
	 *
	 * @return string
	 */
	function lienzo_get_social_image() {
		$image = lienzo_get_social_image_data();

		return isset( $image['url'] ) ? $image['url'] : '';
	}
}

if ( ! function_exists( 'lienzo_get_social_description' ) ) {
	/**
	 * Short description for the current view, used for meta/OG description.
	 *
	 * @return string
	 */
	function lienzo_get_social_description() {
		$description = '';

		if ( is_singular() ) {
			$post = get_queried_object();

			if ( ! empty( $post->post_excerpt ) ) {
				$description = wp_strip_all_tags( $post->post_excerpt );
			} else {
				$description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 35 );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$description = wp_strip_all_tags( term_description() );
		} else {
			$description = get_bloginfo( 'description' );
		}

		return apply_filters( 'lienzo_social_description', $description );
	}
}

if ( ! function_exists( 'lienzo_get_canonical_url' ) ) {
	/**
	 * Canonical URL for the current view (includes pagination when paged).
	 *
	 * @return string
	 */
	function lienzo_get_canonical_url() {
		$url = '';

		if ( is_singular() ) {
			$url = get_permalink();
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$url  = $term ? get_term_link( $term ) : '';
		} elseif ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			$url       = is_string( $post_type ) ? get_post_type_archive_link( $post_type ) : '';
		} elseif ( is_author() ) {
			$url = get_author_posts_url( get_query_var( 'author' ) );
		} elseif ( is_search() ) {
			$url = get_search_link();
		}

		if ( is_wp_error( $url ) ) {
			$url = '';
		}

		if ( '' === $url ) {
			$request = isset( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '';
			$url     = home_url( user_trailingslashit( $request ) );
		}

		return apply_filters( 'lienzo_canonical_url', $url );
	}
}

if ( ! function_exists( 'lienzo_output_canonical' ) ) {
	/**
	 * Print <link rel="canonical"> in <head>.
	 *
	 * @return void
	 */
	function lienzo_output_canonical() {
		if ( ! lienzo_seo_enabled() ) {
			return;
		}

		$url = lienzo_get_canonical_url();

		if ( '' !== $url ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		}
	}
}
add_action( 'wp_head', 'lienzo_output_canonical', 4 );

if ( ! function_exists( 'lienzo_output_social_meta_tags' ) ) {
	/**
	 * Print Open Graph and Twitter Card meta tags in <head>.
	 *
	 * @return void
	 */
	function lienzo_output_social_meta_tags() {
		if ( ! lienzo_seo_enabled() ) {
			return;
		}

		$title       = is_singular() ? get_the_title() : wp_get_document_title();
		$description = lienzo_get_social_description();
		$url         = lienzo_get_canonical_url();
		$image       = lienzo_get_social_image_data();
		$type        = is_singular( 'post' ) ? 'article' : 'website';

		$tags = [
			'og:locale'           => get_locale(),
			'og:title'            => $title,
			'og:description'      => $description,
			'og:type'             => $type,
			'og:url'              => $url,
			'og:site_name'        => get_bloginfo( 'name' ),
			'twitter:card'        => $image ? 'summary_large_image' : 'summary',
			'twitter:title'       => $title,
			'twitter:description' => $description,
		];

		if ( $image ) {
			$tags['og:image']          = $image['url'];
			$tags['twitter:image']     = $image['url'];
			$tags['og:image:alt']      = $image['alt'];
			$tags['twitter:image:alt'] = $image['alt'];

			if ( $image['width'] && $image['height'] ) {
				$tags['og:image:width']  = (string) $image['width'];
				$tags['og:image:height'] = (string) $image['height'];
			}
		}

		if ( 'article' === $type ) {
			$post_id  = get_the_ID();
			$author   = get_post_field( 'post_author', $post_id );
			$tags['article:published_time'] = get_the_date( 'c', $post_id );
			$tags['article:modified_time']  = get_the_modified_date( 'c', $post_id );
			$tags['article:author']         = get_author_posts_url( (int) $author );

			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) ) {
				$tags['article:section'] = $categories[0]->name;
			}

			$tag_names = wp_get_post_tags( $post_id, [ 'fields' => 'names' ] );
			if ( ! empty( $tag_names ) && ! is_wp_error( $tag_names ) ) {
				$tags['article:tag'] = implode( ', ', $tag_names );
			}
		}

		$tags = apply_filters( 'lienzo_social_meta_tags', $tags );

		foreach ( $tags as $property => $content ) {
			if ( '' === $content || null === $content ) {
				continue;
			}

			$attribute = ( 0 === strpos( $property, 'twitter:' ) ) ? 'name' : 'property';

			printf(
				'<meta %1$s="%2$s" content="%3$s">' . "\n",
				esc_attr( $attribute ),
				esc_attr( $property ),
				esc_attr( wp_strip_all_tags( (string) $content ) )
			);
		}
	}
}
add_action( 'wp_head', 'lienzo_output_social_meta_tags', 5 );

if ( ! function_exists( 'lienzo_organization_schema' ) ) {
	/**
	 * Organization node for the site (name, url, logo when available).
	 *
	 * @return array
	 */
	function lienzo_organization_schema() {
		$org = [
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		];

		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$logo    = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;

		if ( $logo ) {
			$org['logo'] = [
				'@type'  => 'ImageObject',
				'url'    => $logo[0],
				'width'  => (int) $logo[1],
				'height' => (int) $logo[2],
			];
		} elseif ( has_site_icon() ) {
			$org['logo'] = [
				'@type' => 'ImageObject',
				'url'   => get_site_icon_url(),
			];
		}

		return $org;
	}
}

if ( ! function_exists( 'lienzo_breadcrumb_schema' ) ) {
	/**
	 * BreadcrumbList node built from the same trail the theme renders
	 * visually — eligible for breadcrumb rich results.
	 *
	 * @return array Empty array when there is no trail (front page, disabled).
	 */
	function lienzo_breadcrumb_schema() {
		if ( ! function_exists( 'lienzo_breadcrumb_trail' ) ) {
			return [];
		}

		$trail = lienzo_breadcrumb_trail();

		if ( count( $trail ) < 2 ) {
			return [];
		}

		$items = [];
		foreach ( $trail as $index => $crumb ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => wp_strip_all_tags( $crumb['label'] ),
				'item'     => '' !== $crumb['url'] ? $crumb['url'] : lienzo_get_canonical_url(),
			];
		}

		return [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		];
	}
}

if ( ! function_exists( 'lienzo_output_structured_data' ) ) {
	/**
	 * Print the JSON-LD graph in <head>: Organization on every page,
	 * WebSite (with SearchAction) on the front page, Article on posts and
	 * BreadcrumbList wherever the breadcrumb trail exists.
	 *
	 * @return void
	 */
	function lienzo_output_structured_data() {
		if ( ! lienzo_seo_enabled() ) {
			return;
		}

		$graph = [ lienzo_organization_schema() ];

		if ( is_front_page() ) {
			$graph[] = [
				'@type'       => 'WebSite',
				'@id'         => home_url( '/#website' ),
				'name'        => get_bloginfo( 'name' ),
				'url'         => home_url( '/' ),
				'publisher'   => [ '@id' => home_url( '/#organization' ) ],
				'potentialAction' => [
					'@type'       => 'SearchAction',
					'target'      => [
						'@type'        => 'EntryPoint',
						'urlTemplate'  => home_url( '/?s={search_term_string}' ),
					],
					'query-input' => 'required name=search_term_string',
				],
			];
		}

		if ( is_singular( 'post' ) ) {
			$post_id = get_the_ID();
			$author  = (int) get_post_field( 'post_author', $post_id );
			$image   = lienzo_get_social_image_data();
			$words   = preg_split( '/\s+/', trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) ) );

			$article = [
				'@type'            => 'Article',
				'headline'         => get_the_title( $post_id ),
				'description'      => lienzo_get_social_description(),
				'datePublished'    => get_the_date( 'c', $post_id ),
				'dateModified'     => get_the_modified_date( 'c', $post_id ),
				'author'           => [
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $author ),
					'url'   => get_author_posts_url( $author ),
				],
				'publisher'        => [ '@id' => home_url( '/#organization' ) ],
				'mainEntityOfPage' => get_permalink( $post_id ),
				'wordCount'        => is_array( $words ) ? count( array_filter( $words ) ) : 0,
			];

			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) ) {
				$article['articleSection'] = $categories[0]->name;
			}

			$tag_names = wp_get_post_tags( $post_id, [ 'fields' => 'names' ] );
			if ( ! empty( $tag_names ) && ! is_wp_error( $tag_names ) ) {
				$article['keywords'] = implode( ', ', $tag_names );
			}

			if ( $image ) {
				$article['image'] = [
					'@type' => 'ImageObject',
					'url'   => $image['url'],
				];
				if ( $image['width'] && $image['height'] ) {
					$article['image']['width']  = $image['width'];
					$article['image']['height'] = $image['height'];
				}
			}

			$graph[] = $article;
		}

		$breadcrumb = lienzo_breadcrumb_schema();
		if ( $breadcrumb ) {
			$graph[] = $breadcrumb;
		}

		$graph = apply_filters( 'lienzo_structured_data', $graph );

		if ( empty( $graph ) ) {
			return;
		}

		foreach ( $graph as &$node ) {
			$node['@context'] = 'https://schema.org';
		}

		echo '<script type="application/ld+json">' . wp_json_encode( count( $graph ) === 1 ? $graph[0] : $graph ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'lienzo_output_structured_data', 6 );

if ( ! function_exists( 'lienzo_robots_directives' ) ) {
	/**
	 * Open up indexing previews (large images, full snippets) when Lienzo's
	 * own SEO layer is the one in charge.
	 *
	 * @param array $robots Robots directives from wp_robots().
	 *
	 * @return array
	 */
	function lienzo_robots_directives( $robots ) {
		if ( ! lienzo_seo_enabled() ) {
			return $robots;
		}

		$robots['max-image-preview'] = 'large';
		$robots['max-snippet']       = '-1';
		$robots['max-video-preview'] = '-1';

		return $robots;
	}
}
add_filter( 'wp_robots', 'lienzo_robots_directives' );

if ( ! function_exists( 'lienzo_attachment_image_attrs' ) ) {
	/**
	 * Async-decode attachment images the theme outputs — faster rendering,
	 * especially on image-heavy archives.
	 *
	 * @param array $attr Image attributes.
	 *
	 * @return array
	 */
	function lienzo_attachment_image_attrs( $attr ) {
		if ( ! apply_filters( 'lienzo_image_decoding_async', true ) ) {
			return $attr;
		}

		if ( empty( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}

		return $attr;
	}
}
add_filter( 'wp_get_attachment_image_attributes', 'lienzo_attachment_image_attrs' );
