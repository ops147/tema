<?php
/**
 * "Lienzo Reading Time" Elementor widget.
 *
 * @package Lienzo
 */

namespace Lienzo\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Prints the estimated reading time of the current post; meant for the
 * Single Post Theme Builder template.
 */
class Reading_Time_Widget extends Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'lienzo_reading_time';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Lienzo Astra Reading Time', 'lienzo-astra' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-clock-o';
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
		return [ 'reading time', 'estimated time', 'blog', 'lienzo-astra' ];
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
				'label' => esc_html__( 'Reading time', 'lienzo-astra' ),
			]
		);

		$this->add_control(
			'icon_display',
			[
				'label'     => esc_html__( 'Show clock icon', 'lienzo-astra' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => esc_html__( 'Yes', 'lienzo-astra' ),
				'label_off' => esc_html__( 'No', 'lienzo-astra' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'lienzo-astra' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Text color', 'lienzo-astra' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .lienzo-reading-time' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .lienzo-reading-time',
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
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		if ( ! $post_id ) {
			return;
		}
		?>
		<span class="lienzo-reading-time">
			<?php if ( 'yes' === $settings['icon_display'] ) : ?>
				<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
			<?php endif; ?>
			<?php echo lienzo_reading_time_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
		<?php
	}
}
