<?php
/**
 * Block patterns — ready-made starter sections for the block editor.
 *
 * In the spirit of Astra's and Kadence's starter templates, each file in
 * /patterns describes a complete section (hero, features, pricing…) built
 * only with core blocks and the theme's global color palette, so it works
 * with or without Elementor and always matches the site's Design Options.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzoastra_register_block_patterns' ) ) {
	/**
	 * Register the "Lienzo Astra" pattern category plus every pattern file
	 * found in /patterns. Patterns are always registered: there is no
	 * switch or filter that can hide them from the editor.
	 *
	 * Pattern files use the standard WordPress header format (Title, Slug,
	 * Categories…); their markup is captured by including the file, so
	 * patterns can contain translatable strings.
	 *
	 * @return void
	 */
	function lienzoastra_register_block_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			'lienzo-astra',
			[ 'label' => __( 'Lienzo Astra', 'lienzo-astra' ) ]
		);

		$files = glob( LIENZOASTRA_DIR . '/patterns/*.php' );

		if ( ! is_array( $files ) ) {
			return;
		}

		foreach ( $files as $file ) {
			$pattern = get_file_data(
				$file,
				[
					'title'         => 'Title',
					'slug'          => 'Slug',
					'description'   => 'Description',
					'categories'    => 'Categories',
					'keywords'      => 'Keywords',
					'viewportWidth' => 'Viewport Width',
					'blockTypes'    => 'Block Types',
					'postTypes'     => 'Post Types',
					'inserter'      => 'Inserter',
				]
			);

			if ( empty( $pattern['slug'] ) || empty( $pattern['title'] ) ) {
				continue;
			}

			ob_start();
			include $file;
			$content = (string) ob_get_clean();

			register_block_pattern(
				$pattern['slug'],
				[
					'title'         => $pattern['title'],
					'description'   => $pattern['description'],
					'content'       => $content,
					'categories'    => array_filter( array_map( 'trim', explode( ',', (string) $pattern['categories'] ) ) ),
					'keywords'      => array_filter( array_map( 'trim', explode( ',', (string) $pattern['keywords'] ) ) ),
					'viewportWidth' => (int) $pattern['viewportWidth'],
					'blockTypes'    => array_filter( array_map( 'trim', explode( ',', (string) $pattern['blockTypes'] ) ) ),
					'postTypes'     => array_filter( array_map( 'trim', explode( ',', (string) $pattern['postTypes'] ) ) ),
					'inserter'      => 'no' === $pattern['inserter'] ? false : true,
				]
			);
		}
	}
}
add_action( 'init', 'lienzoastra_register_block_patterns' );
