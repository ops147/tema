<?php
/**
 * "Lienzo Dark Mode Switcher" Elementor widget.
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
 * Lets you place the dark/light mode toggle anywhere (menu, header, footer)
 * instead of relying on the automatic fixed button.
 */
class Dark_Mode_Widget extends Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'lienzo_dark_mode';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Lienzo Astra Dark Mode Switcher', 'lienzo-astra' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nerd';
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
		return [ 'dark mode', 'light mode', 'theme switcher', 'lienzo-astra' ];
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
				'label' => esc_html__( 'Switcher', 'lienzo-astra' ),
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => esc_html__( 'Show text label', 'lienzo-astra' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => esc_html__( 'Yes', 'lienzo-astra' ),
				'label_off'    => esc_html__( 'No', 'lienzo-astra' ),
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
			'icon_color',
			[
				'label'     => esc_html__( 'Icon color', 'lienzo-astra' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .lienzo-dark-toggle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'bg_color',
			[
				'label'     => esc_html__( 'Background color', 'lienzo-astra' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .lienzo-dark-toggle' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .lienzo-dark-toggle-label',
				'condition' => [
					'show_label' => 'yes',
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
		$settings = $this->get_settings_for_display();
		?>
		<button type="button" class="lienzo-dark-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle dark mode', 'lienzo-astra' ); ?>">
			<span class="lienzo-dark-toggle-icon" aria-hidden="true">
				<svg class="icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
				<svg class="icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>
			</span>
			<?php if ( 'yes' === $settings['show_label'] ) : ?>
				<span class="lienzo-dark-toggle-label"><?php echo esc_html__( 'Dark mode', 'lienzo-astra' ); ?></span>
			<?php endif; ?>
		</button>
		<?php
	}
}
