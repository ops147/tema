<?php
/**
 * "Lienzo Breadcrumbs" Elementor widget.
 *
 * @package Lienzo
 */

namespace Lienzo\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Drops the theme's breadcrumb trail anywhere in the Elementor editor.
 */
class Breadcrumbs_Widget extends Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'lienzo_breadcrumbs';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Lienzo Astra Breadcrumbs', 'lienzo-astra' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-post-navigation';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'lienzo-astra' ];
	}

	/**
	 * Keywords used by the widgets panel search.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'breadcrumbs', 'navigation', 'trail', 'lienzo-astra' ];
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Breadcrumbs', 'lienzo-astra' ),
			]
		);

		$this->add_control(
			'alignment',
			[
				'label'   => esc_html__( 'Alignment', 'lienzo-astra' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [
						'title' => esc_html__( 'Left', 'lienzo-astra' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'lienzo-astra' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'lienzo-astra' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default' => 'left',
				'selectors_dictionary' => [
					'left'   => 'flex-start',
					'center' => 'center',
					'right'  => 'flex-end',
				],
				'selectors' => [
					'{{WRAPPER}} .lienzo-breadcrumbs ol' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render on the front end.
	 *
	 * @return void
	 */
	protected function render() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<nav class="lienzo-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'lienzo-astra' ) . '"><ol><li><a href="#">' . esc_html__( 'Home', 'lienzo-astra' ) . '</a></li><li><span aria-current="page">' . esc_html__( 'Example page', 'lienzo-astra' ) . '</span></li></ol></nav>';
			return;
		}

		echo lienzo_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
