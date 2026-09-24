<?php
/**
 * Blog helpers — archive thumbnails, excerpt length and post meta.
 *
 * The matching controls live in Design Options > Blog; these functions
 * consume them so the fallback templates stay consistent with what
 * Blocksy/Astra-style blog panels offer.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzoastra_blog_show_thumbnail' ) ) {
	/**
	 * Whether archive/search loops should print featured images.
	 *
	 * @return bool
	 */
	function lienzoastra_blog_show_thumbnail() {
		return (bool) apply_filters( 'lienzoastra_blog_thumbnail', (bool) lienzoastra_get_option( 'blog_thumbnails' ) );
	}
}

if ( ! function_exists( 'lienzoastra_blog_show_meta' ) ) {
	/**
	 * Whether single posts should print the meta line (date, author, cats).
	 *
	 * @return bool
	 */
	function lienzoastra_blog_show_meta() {
		return (bool) apply_filters( 'lienzoastra_blog_post_meta', (bool) lienzoastra_get_option( 'blog_post_meta' ) );
	}
}

if ( ! function_exists( 'lienzoastra_post_meta' ) ) {
	/**
	 * Post meta line for single posts: publish date, author and categories.
	 *
	 * @return string HTML, or an empty string when disabled/not a post.
	 */
	function lienzoastra_post_meta() {
		if ( 'post' !== get_post_type() || ! lienzoastra_blog_show_meta() ) {
			return '';
		}

		$meta = [];

		$meta[] = sprintf(
			'<time class="post-meta-date" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() )
		);

		$meta[] = sprintf(
			'<span class="post-meta-author"><a href="%1$s">%2$s</a></span>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		);

		$categories = get_the_category_list( ', ' );
		if ( $categories ) {
			$meta[] = sprintf( '<span class="post-meta-categories">%s</span>', wp_kses_post( $categories ) );
		}

		$meta = apply_filters( 'lienzoastra_post_meta_items', $meta );

		if ( empty( $meta ) ) {
			return '';
		}

		return '<div class="post-meta">' . implode( '<span class="post-meta-sep" aria-hidden="true">·</span>', $meta ) . '</div>';
	}
}

if ( ! function_exists( 'lienzoastra_excerpt_length' ) ) {
	/**
	 * Apply the "Excerpt length" option to automatic excerpts.
	 *
	 * @param int $length WordPress default (55).
	 *
	 * @return int
	 */
	function lienzoastra_excerpt_length( $length ) {
		$saved = (int) lienzoastra_get_option( 'blog_excerpt_length' );

		return $saved > 0 ? $saved : $length;
	}
}
add_filter( 'excerpt_length', 'lienzoastra_excerpt_length' );

if ( ! function_exists( 'lienzoastra_excerpt_more' ) ) {
	/**
	 * Tidy ellipsis after automatic excerpts.
	 *
	 * @param string $more WordPress default ([&hellip;]).
	 *
	 * @return string
	 */
	function lienzoastra_excerpt_more( $more ) {
		return '&hellip;';
	}
}
add_filter( 'excerpt_more', 'lienzoastra_excerpt_more' );
