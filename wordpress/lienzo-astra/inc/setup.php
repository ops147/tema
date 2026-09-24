<?php
/**
 * Theme setup: features, menus, content width and <head> tweaks.
 *
 * Every function is wrapped in `function_exists()` so a child theme can
 * override it, and every behaviour can be switched off through a filter.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function lienzo_setup() {
		// The theme ships its own translations in /languages (it is not
		// distributed through wordpress.org, so they must be loaded manually).
		load_theme_textdomain( 'lienzo-astra', LIENZOASTRA_DIR . '/languages' );

		if ( apply_filters( 'lienzo_register_menus', true ) ) {
			register_nav_menus(
				[
					'menu-1' => esc_html__( 'Header', 'lienzo-astra' ),
					'menu-2' => esc_html__( 'Footer', 'lienzo-astra' ),
				]
			);
		}

		if ( apply_filters( 'lienzo_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'lienzo_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
					'navigation-widgets',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );
			add_theme_support( 'customize-selective-refresh-widgets' );

			// Core block styles on the front end (theme.json opts the site
			// into the block editor, so blocks should render faithfully).
			add_theme_support( 'wp-block-styles' );

			// Block editor styles.
			add_theme_support( 'editor-styles' );
			add_editor_style( 'assets/css/editor-styles.css' );

			// PAge plugin connection: opaque, encrypted image URLs on the
			// theme's front-end output (ImageShield buffers the page when
			// the plugin is active; inert otherwise).
			add_theme_support( 'page-clones-img-shield' );

			// WooCommerce.
			if ( apply_filters( 'lienzo_add_woocommerce_support', true ) ) {
				add_theme_support( 'woocommerce' );
				add_theme_support( 'wc-product-gallery-zoom' );
				add_theme_support( 'wc-product-gallery-lightbox' );
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'lienzo_setup' );

if ( ! function_exists( 'lienzo_content_width' ) ) {
	/**
	 * Set the default content width.
	 *
	 * @return void
	 */
	function lienzo_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'lienzo_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'lienzo_content_width', 0 );

if ( ! function_exists( 'lienzo_display_header_footer' ) ) {
	/**
	 * Whether the theme should render its own header and footer.
	 *
	 * @return bool
	 */
	function lienzo_display_header_footer() {
		return (bool) apply_filters( 'lienzo_header_footer', true );
	}
}

if ( ! function_exists( 'lienzo_add_description_meta_tag' ) ) {
	/**
	 * Print a description meta tag built from the excerpt of the current post.
	 *
	 * @return void
	 */
	function lienzo_add_description_meta_tag() {
		if ( ! apply_filters( 'lienzo_description_meta_tag', true ) ) {
			return;
		}

		$description = '';

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			}
		} elseif ( is_front_page() || is_home() ) {
			$description = get_bloginfo( 'description' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$description = term_description();
		}

		$description = trim( wp_strip_all_tags( (string) $description ) );

		if ( '' === $description ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'lienzo_add_description_meta_tag' );
