<?php
/**
 * Registers a "Lienzo" Elementor widget category plus a small set of
 * widgets that expose the theme's own features (breadcrumbs, dark mode
 * switcher, reading time) as draggable elements.
 *
 * Nothing here runs unless Elementor is active.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_register_elementor_category' ) ) {
	/**
	 * Add the "Lienzo" category to the widgets panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 *
	 * @return void
	 */
	function lienzo_register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'lienzo-astra',
			[
				'title' => esc_html__( 'Lienzo Astra', 'lienzo-astra' ),
				'icon'  => 'eicon-theme-builder',
			]
		);
	}
}
add_action( 'elementor/elements/categories_registered', 'lienzo_register_elementor_category' );

if ( ! function_exists( 'lienzo_register_elementor_widgets' ) ) {
	/**
	 * Load and register the theme's Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 *
	 * @return void
	 */
	function lienzo_register_elementor_widgets( $widgets_manager ) {
		if ( ! apply_filters( 'lienzo_register_elementor_widgets', true ) ) {
			return;
		}

		require_once __DIR__ . '/widgets/class-breadcrumbs-widget.php';
		require_once __DIR__ . '/widgets/class-dark-mode-widget.php';
		require_once __DIR__ . '/widgets/class-reading-time-widget.php';

		$widgets_manager->register( new \Lienzo\Widgets\Breadcrumbs_Widget() );
		$widgets_manager->register( new \Lienzo\Widgets\Dark_Mode_Widget() );
		$widgets_manager->register( new \Lienzo\Widgets\Reading_Time_Widget() );
	}
}
add_action( 'elementor/widgets/register', 'lienzo_register_elementor_widgets' );
