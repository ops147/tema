<?php

namespace Lienzo\Settings;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Tab_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Settings_Footer extends Tab_Base {

	public function get_id() {
		return 'lienzo-settings-footer';
	}

	public function get_title() {
		return esc_html__( 'Lienzo Astra Footer', 'lienzo-astra' );
	}

	public function get_icon() {
		return 'eicon-footer';
	}

	public function get_help_url() {
		return '';
	}

	public function get_group() {
		return 'theme-style';
	}

	protected function register_tab_controls() {
		$start = is_rtl() ? 'right' : 'left';
		$end = ! is_rtl() ? 'right' : 'left';

		$this->start_controls_section(
			'lienzo_footer_section',
			[
				'tab' => 'lienzo-settings-footer',
				'label' => esc_html__( 'Footer', 'lienzo-astra' ),
			]
		);

		$this->add_control(
			'lienzo_footer_logo_display',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Site Logo', 'lienzo-astra' ),
				'default' => 'yes',
				'label_on' => esc_html__( 'Show', 'lienzo-astra' ),
				'label_off' => esc_html__( 'Hide', 'lienzo-astra' ),
				'selector' => '.site-footer .site-branding',
			]
		);

		$this->add_control(
			'lienzo_footer_tagline_display',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Tagline', 'lienzo-astra' ),
				'default' => 'yes',
				'label_on' => esc_html__( 'Show', 'lienzo-astra' ),
				'label_off' => esc_html__( 'Hide', 'lienzo-astra' ),
				'selector' => '.site-footer .site-description',
			]
		);

		$this->add_control(
			'lienzo_footer_menu_display',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Menu', 'lienzo-astra' ),
				'default' => 'yes',
				'label_on' => esc_html__( 'Show', 'lienzo-astra' ),
				'label_off' => esc_html__( 'Hide', 'lienzo-astra' ),
				'selector' => '.site-footer .site-navigation',
			]
		);

		$this->add_control(
			'lienzo_footer_copyright_display',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Copyright', 'lienzo-astra' ),
				'default' => 'yes',
				'label_on' => esc_html__( 'Show', 'lienzo-astra' ),
				'label_off' => esc_html__( 'Hide', 'lienzo-astra' ),
				'selector' => '.site-footer .copyright',
			]
		);

		$this->add_control(
			'lienzo_footer_disable_note',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'warning',
				'content' => sprintf(
					/* translators: %s: Link that opens the theme settings page. */
					__( 'Note: Hiding all the elements, only hides them visually. To disable them completely go to <a href="%s">Theme Settings</a> .', 'lienzo-astra' ),
					admin_url( 'themes.php?page=lienzo-settings' )
				),
				'render_type' => 'ui',
				'condition' => [
					'lienzo_footer_logo_display' => '',
					'lienzo_footer_tagline_display' => '',
					'lienzo_footer_menu_display' => '',
					'lienzo_footer_copyright_display' => '',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_layout',
			[
				'type' => Controls_Manager::CHOOSE,
				'label' => esc_html__( 'Layout', 'lienzo-astra' ),
				'options' => [
					'inverted' => [
						'title' => esc_html__( 'Inverted', 'lienzo-astra' ),
						'icon' => "eicon-arrow-$start",
					],
					'stacked' => [
						'title' => esc_html__( 'Centered', 'lienzo-astra' ),
						'icon' => 'eicon-h-align-center',
					],
					'default' => [
						'title' => esc_html__( 'Default', 'lienzo-astra' ),
						'icon' => "eicon-arrow-$end",
					],
				],
				'toggle' => false,
				'selector' => '.site-footer',
				'default' => 'default',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'lienzo_footer_tagline_position',
			[
				'type' => Controls_Manager::CHOOSE,
				'label' => esc_html__( 'Tagline Position', 'lienzo-astra' ),
				'options' => [
					'before' => [
						'title' => esc_html__( 'Before', 'lienzo-astra' ),
						'icon' => "eicon-arrow-$start",
					],
					'below' => [
						'title' => esc_html__( 'Below', 'lienzo-astra' ),
						'icon' => 'eicon-arrow-down',
					],
					'after' => [
						'title' => esc_html__( 'After', 'lienzo-astra' ),
						'icon' => "eicon-arrow-$end",
					],
				],
				'toggle' => false,
				'default' => 'below',
				'selectors_dictionary' => [
					'before' => 'flex-direction: row-reverse; align-items: center;',
					'below' => 'flex-direction: column; align-items: stretch;',
					'after' => 'flex-direction: row; align-items: center;',
				],
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
					'lienzo_footer_logo_display' => 'yes',
				],
				'selectors' => [
					'.site-footer .site-branding' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'lienzo_footer_tagline_gap',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Tagline Gap', 'lienzo-astra' ),
				'size_units' => [ 'px', 'em ', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
					'lienzo_footer_logo_display' => 'yes',
				],
				'selectors' => [
					'.site-footer .site-branding' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_width',
			[
				'type' => Controls_Manager::SELECT,
				'label' => esc_html__( 'Width', 'lienzo-astra' ),
				'options' => [
					'boxed' => esc_html__( 'Boxed', 'lienzo-astra' ),
					'full-width' => esc_html__( 'Full Width', 'lienzo-astra' ),
				],
				'selector' => '.site-footer',
				'default' => 'boxed',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'lienzo_footer_custom_width',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Content Width', 'lienzo-astra' ),
				'size_units' => [ '%', 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 2000,
					],
					'em' => [
						'max' => 100,
					],
					'rem' => [
						'max' => 100,
					],
				],
				'condition' => [
					'lienzo_footer_width' => 'boxed',
				],
				'selectors' => [
					'.site-footer .footer-inner' => 'width: {{SIZE}}{{UNIT}}; max-width: 100%;',
				],
			]
		);

		$this->add_responsive_control(
			'lienzo_footer_gap',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Side Margins', 'lienzo-astra' ),
				'size_units' => [ '%', 'px', 'em ', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
				],
				'selectors' => [
					'.site-footer' => 'padding-inline-end: {{SIZE}}{{UNIT}}; padding-inline-start: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'lienzo_footer_layout!' => 'stacked',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'lienzo_footer_background',
				'label' => esc_html__( 'Background', 'lienzo-astra' ),
				'types' => [ 'classic', 'gradient' ],
				'separator' => 'before',
				'selector' => '.site-footer',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'lienzo_footer_logo_section',
			[
				'tab' => 'lienzo-settings-footer',
				'label' => esc_html__( 'Site Logo', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display!' => '',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_logo_link',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => sprintf(
					/* translators: %s: Link that opens Elementor's "Site Identity" panel. */
					__( 'Go to <a href="%s">Site Identity</a> to manage your site\'s logo', 'lienzo-astra' ),
					"javascript:\$e.route('panel/global/settings-site-identity')"
				),
				'render_type' => 'ui',
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'logo',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_title_link',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => sprintf(
					/* translators: %s: Link that opens Elementor's "Site Identity" panel. */
					__( 'Go to <a href="%s">Site Identity</a> to manage your site\'s title', 'lienzo-astra' ),
					"javascript:\$e.route('panel/global/settings-site-identity')"
				),
				'render_type' => 'ui',
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_logo_type',
			[
				'label' => esc_html__( 'Type', 'lienzo-astra' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'logo',
				'options' => [
					'logo' => esc_html__( 'Logo', 'lienzo-astra' ),
					'title' => esc_html__( 'Title', 'lienzo-astra' ),
				],
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'lienzo_footer_logo_width',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Logo Width', 'lienzo-astra' ),
				'size_units' => [ '%', 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 1000,
					],
					'em' => [
						'max' => 100,
					],
					'rem' => [
						'max' => 100,
					],
				],
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'logo',
				],
				'selectors' => [
					'.site-footer .site-branding .site-logo img' => 'width: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'lienzo_footer_title_typography',
				'label' => esc_html__( 'Typography', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
				'selector' => '.site-footer .site-title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'lienzo_footer_title_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
				'selector' => '.site-footer .site-title a',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'lienzo_footer_title_text_stroke',
				'label' => esc_html__( 'Text Stroke', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
				'selector' => '.site-footer .site-title a',
			]
		);

		$this->start_controls_tabs( 'lienzo_footer_title_colors' );

		$this->start_controls_tab(
			'lienzo_footer_title_colors_normal',
			[
				'label' => esc_html__( 'Normal', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_title_color',
			[
				'label' => esc_html__( 'Text Color', 'lienzo-astra' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
				'selectors' => [
					'.site-footer .site-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'lienzo_footer_title_colors_hover',
			[
				'label' => esc_html__( 'Hover', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_title_hover_color',
			[
				'label' => esc_html__( 'Text Color', 'lienzo-astra' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'lienzo_footer_logo_display' => 'yes',
					'lienzo_footer_logo_type' => 'title',
				],
				'selectors' => [
					'.site-footer .site-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_title_hover_color_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'lienzo-astra' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 's',
				],
				'selectors' => [
					'.site-footer .site-title a' => 'transition-duration: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section(
			'lienzo_footer_tagline',
			[
				'tab' => 'lienzo-settings-footer',
				'label' => esc_html__( 'Tagline', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_tagline_link',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => sprintf(
					/* translators: %s: Link that opens Elementor's "Site Identity" panel. */
					__( 'Go to <a href="%s">Site Identity</a> to manage your site\'s tagline', 'lienzo-astra' ),
					"javascript:\$e.route('panel/global/settings-site-identity')"
				),
				'render_type' => 'ui',
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
				],
			]
		);

		$this->add_control(
			'lienzo_footer_tagline_color',
			[
				'label' => esc_html__( 'Text Color', 'lienzo-astra' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
				],
				'selectors' => [
					'.site-footer .site-description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'lienzo_footer_tagline_typography',
				'label' => esc_html__( 'Typography', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
				],
				'selector' => '.site-footer .site-description',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'lienzo_footer_tagline_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_tagline_display' => 'yes',
				],
				'selector' => '.site-footer .site-description',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'lienzo_footer_menu_tab',
			[
				'tab' => 'lienzo-settings-footer',
				'label' => esc_html__( 'Menu', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_menu_display' => 'yes',
				],
			]
		);

		$available_menus = wp_get_nav_menus();

		$menus = [ '0' => esc_html__( '— Select a Menu —', 'lienzo-astra' ) ];
		foreach ( $available_menus as $available_menu ) {
			$menus[ $available_menu->term_id ] = $available_menu->name;
		}

		if ( 1 === count( $menus ) ) {
			$this->add_control(
				'lienzo_footer_menu_notice',
				[
					'type' => Controls_Manager::ALERT,
					'alert_type' => 'info',
					'heading' => esc_html__( 'There are no menus in your site.', 'lienzo-astra' ),
					'content' => sprintf(
						/* translators: %s: Link that opens the menus screen. */
						__( 'Go to <a href="%s" target="_blank">Menus screen</a> to create one.', 'lienzo-astra' ),
						admin_url( 'nav-menus.php?action=edit&menu=0' )
					),
					'render_type' => 'ui',
				]
			);
		} else {
			$this->add_control(
				'lienzo_footer_menu_warning',
				[
					'type' => Controls_Manager::ALERT,
					'alert_type' => 'info',
					'content' => sprintf(
						/* translators: %s: Link that opens the menus screen. */
						__( 'Go to the <a href="%s" target="_blank">Menus screen</a> to manage your menus. Changes will be reflected in the preview only after the page reloads.', 'lienzo-astra' ),
						admin_url( 'nav-menus.php' )
					),
					'render_type' => 'ui',
				]
			);

			$this->add_control(
				'lienzo_footer_menu',
				[
					'label' => esc_html__( 'Menu', 'lienzo-astra' ),
					'type' => Controls_Manager::SELECT,
					'options' => $menus,
					'default' => array_keys( $menus )[0],
				]
			);

			$this->add_control(
				'lienzo_footer_menu_color',
				[
					'label' => esc_html__( 'Color', 'lienzo-astra' ),
					'type' => Controls_Manager::COLOR,
					'selectors' => [
						'footer .footer-inner .site-navigation a' => 'color: {{VALUE}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				[
					'name' => 'lienzo_footer_menu_typography',
					'label' => esc_html__( 'Typography', 'lienzo-astra' ),
					'selector' => 'footer .footer-inner .site-navigation a',
				]
			);

			$this->add_group_control(
				Group_Control_Text_Shadow::get_type(),
				[
					'name' => 'lienzo_footer_menu_text_shadow',
					'label' => esc_html__( 'Text Shadow', 'lienzo-astra' ),
					'selector' => 'footer .footer-inner .site-navigation a',
				]
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'lienzo_footer_copyright_section',
			[
				'tab' => 'lienzo-settings-footer',
				'label' => esc_html__( 'Copyright', 'lienzo-astra' ),
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name' => 'lienzo_footer_copyright_display',
							'operator' => '=',
							'value' => 'yes',
						],
					],
				],
			]
		);

		$this->add_control(
			'lienzo_footer_copyright_text',
			[
				'type' => Controls_Manager::TEXTAREA,
				'label' => esc_html__( 'Text', 'lienzo-astra' ),
				'default' => esc_html__( 'All rights reserved', 'lienzo-astra' ),
			]
		);

		$this->add_control(
			'lienzo_footer_copyright_color',
			[
				'label' => esc_html__( 'Text Color', 'lienzo-astra' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'lienzo_footer_copyright_display' => 'yes',
				],
				'selectors' => [
					'.site-footer .copyright p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'lienzo_footer_copyright_typography',
				'label' => esc_html__( 'Typography', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_copyright_display' => 'yes',
				],
				'selector' => '.site-footer .copyright p',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'lienzo_footer_copyright_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'lienzo-astra' ),
				'condition' => [
					'lienzo_footer_copyright_display' => 'yes',
				],
				'selector' => '.site-footer .copyright p',
			]
		);

		$this->end_controls_section();
	}

	public function on_save( $data ) {
		// Save chosen footer menu to the WP settings.
		if ( isset( $data['settings']['lienzo_footer_menu'] ) ) {
			$menu_id = $data['settings']['lienzo_footer_menu'];
			$locations = get_theme_mod( 'nav_menu_locations' );
			$locations['menu-2'] = (int) $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	/**
	 * Point Theme Builder users (Elementor Pro) to the footer template screen.
	 * Nothing is rendered when Elementor Pro is not active.
	 *
	 * @return string
	 */
	public function get_additional_tab_content() {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return '';
		}

		return sprintf(
			'<p class="elementor-control-field-description">%1$s <a href="%2$s" target="_blank">%3$s</a></p>',
			esc_html__( 'Need a fully custom footer? Build one with the Theme Builder.', 'lienzo-astra' ),
			esc_url( get_admin_url( null, 'admin.php?page=elementor-app#/site-editor/templates/footer' ) ),
			esc_html__( 'Create Footer', 'lienzo-astra' )
		);
	}
}
