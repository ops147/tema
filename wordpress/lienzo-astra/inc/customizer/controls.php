<?php
/**
 * Customizer — panel, sections, settings, controls and live preview.
 *
 * Split out of inc/customizer.php (the Design Options loader) so each
 * concern lives in its own file.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 *
 * @return void
 */
function lienzoastra_customize_register( $wp_customize ) {
	$defaults = lienzoastra_customizer_defaults();

	$wp_customize->add_panel(
		'lienzoastra_design_options',
		[
			'title'    => __( 'Design Options', 'lienzo-astra' ),
			'priority' => 45,
		]
	);

	/* ---------- Colors ---------- */
	$wp_customize->add_section(
		'lienzoastra_colors',
		[
			'title' => __( 'Colors', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$palette_choices = [];
	foreach ( lienzoastra_color_palettes() as $slug => $palette ) {
		$palette_choices[ $slug ] = $palette['label'];
	}

	$wp_customize->add_setting(
		'lienzoastra_color_palette',
		[
			'default'           => $defaults['color_palette'],
			'sanitize_callback' => 'lienzoastra_sanitize_palette',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_color_palette',
		[
			'label'       => __( 'Preset palette', 'lienzo-astra' ),
			'description' => __( 'Choosing a palette fills the color controls below; you can still fine-tune each color afterwards.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_colors',
			'type'        => 'select',
			'choices'     => $palette_choices,
		]
	);

	$color_settings = [
		'color_primary'    => __( 'Primary color', 'lienzo-astra' ),
		'color_link'       => __( 'Link color', 'lienzo-astra' ),
		'color_link_hover' => __( 'Link hover color', 'lienzo-astra' ),
		'color_text'       => __( 'Text color', 'lienzo-astra' ),
		'color_heading'    => __( 'Headings color', 'lienzo-astra' ),
		'color_background' => __( 'Background color', 'lienzo-astra' ),
	];

	foreach ( $color_settings as $key => $label ) {
		$wp_customize->add_setting(
			'lienzoastra_' . $key,
			[
				'default'           => $defaults[ $key ],
				// sanitize_hex_color() passes '' through, so the optional
				// colors can stay unset and fall back to a base color.
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'lienzoastra_' . $key,
				[
					'label'   => $label,
					'section' => 'lienzoastra_colors',
				]
			)
		);
	}

	$dark_color_settings = [
		'dark_bg_color'   => __( 'Dark mode — background', 'lienzo-astra' ),
		'dark_text_color' => __( 'Dark mode — text', 'lienzo-astra' ),
		'dark_link_color' => __( 'Dark mode — links & accents', 'lienzo-astra' ),
	];

	foreach ( $dark_color_settings as $key => $label ) {
		$wp_customize->add_setting(
			'lienzoastra_' . $key,
			[
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'sanitize_hex_color',
				// Refresh transport: binding these to :root vars would leak
				// dark values into light-mode elements that read --lienzo-*.
				'transport'         => 'refresh',
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'lienzoastra_' . $key,
				[
					'label'       => $label,
					// Show the hint once, on the first dark-mode row.
					'description' => 'dark_bg_color' === $key ? __( 'Only applies when the site is switched to dark mode. Leave empty to keep the built-in dark palette.', 'lienzo-astra' ) : '',
					'section'     => 'lienzoastra_colors',
				]
			)
		);
	}

	/* ---------- Typography ---------- */
	$wp_customize->add_section(
		'lienzoastra_typography',
		[
			'title' => __( 'Typography', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_font_family_body',
		[
			'default'           => $defaults['font_family_body'],
			'sanitize_callback' => 'lienzoastra_sanitize_font_choice',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_font_family_body',
		[
			'label'   => __( 'Body font', 'lienzo-astra' ),
			'section' => 'lienzoastra_typography',
			'type'    => 'select',
			'choices' => lienzoastra_font_choices(),
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_font_family_heading',
		[
			'default'           => $defaults['font_family_heading'],
			'sanitize_callback' => 'lienzoastra_sanitize_font_choice',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_font_family_heading',
		[
			'label'   => __( 'Headings font', 'lienzo-astra' ),
			'section' => 'lienzoastra_typography',
			'type'    => 'select',
			'choices' => lienzoastra_font_choices(),
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_font_size_base',
		[
			'default'           => $defaults['font_size_base'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_font_size_base',
		[
			'label'       => __( 'Base font size (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_typography',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 12,
				'max'  => 22,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_line_height_base',
		[
			'default'           => $defaults['line_height_base'],
			'sanitize_callback' => 'lienzoastra_sanitize_line_height',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_line_height_base',
		[
			'label'       => __( 'Body line height', 'lienzo-astra' ),
			'section'     => 'lienzoastra_typography',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 1.2,
				'max'  => 2.2,
				'step' => 0.05,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_font_weight_heading',
		[
			'default'           => $defaults['font_weight_heading'],
			'sanitize_callback' => 'lienzoastra_sanitize_heading_weight',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_font_weight_heading',
		[
			'label'   => __( 'Headings font weight', 'lienzo-astra' ),
			'section' => 'lienzoastra_typography',
			'type'    => 'select',
			'choices' => [
				''    => __( 'Inherit', 'lienzo-astra' ),
				'300' => '300 (Light)',
				'400' => '400 (Regular)',
				'500' => '500 (Medium)',
				'600' => '600 (Semibold)',
				'700' => '700 (Bold)',
				'800' => '800 (Extra bold)',
				'900' => '900 (Black)',
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_heading_text_transform',
		[
			'default'           => $defaults['heading_text_transform'],
			'sanitize_callback' => 'lienzoastra_sanitize_text_transform',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_heading_text_transform',
		[
			'label'   => __( 'Headings text transform', 'lienzo-astra' ),
			'section' => 'lienzoastra_typography',
			'type'    => 'select',
			'choices' => [
				'none'       => __( 'None', 'lienzo-astra' ),
				'uppercase'  => __( 'Uppercase', 'lienzo-astra' ),
				'capitalize' => __( 'Capitalize', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_heading_letter_spacing',
		[
			'default'           => $defaults['heading_letter_spacing'],
			'sanitize_callback' => 'lienzoastra_sanitize_letter_spacing',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_heading_letter_spacing',
		[
			'label'       => __( 'Headings letter spacing (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_typography',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => -2,
				'max'  => 5,
				'step' => 0.5,
			],
		]
	);

	/* ---------- Buttons ---------- */
	$wp_customize->add_section(
		'lienzoastra_buttons',
		[
			'title' => __( 'Buttons', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$button_color_settings = [
		'btn_bg'         => __( 'Background color', 'lienzo-astra' ),
		'btn_text'       => __( 'Text color', 'lienzo-astra' ),
		'btn_bg_hover'   => __( 'Background on hover', 'lienzo-astra' ),
		'btn_text_hover' => __( 'Text color on hover', 'lienzo-astra' ),
	];

	foreach ( $button_color_settings as $key => $label ) {
		$wp_customize->add_setting(
			'lienzoastra_' . $key,
			[
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'lienzoastra_' . $key,
				[
					'label'       => $label,
					// Show the hint once, on the first button row.
					'description' => 'btn_bg' === $key ? __( 'Applies to content buttons: blocks, forms, WooCommerce and Elementor buttons. Empty colors fall back to the link palette.', 'lienzo-astra' ) : '',
					'section'     => 'lienzoastra_buttons',
				]
			)
		);
	}

	$wp_customize->add_setting(
		'lienzoastra_btn_radius',
		[
			'default'           => $defaults['btn_radius'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_btn_radius',
		[
			'label'       => __( 'Corner radius (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_buttons',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 30,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_btn_padding_v',
		[
			'default'           => $defaults['btn_padding_v'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_btn_padding_v',
		[
			'label'       => __( 'Vertical padding (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_buttons',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 4,
				'max'  => 30,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_btn_padding_h',
		[
			'default'           => $defaults['btn_padding_h'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_btn_padding_h',
		[
			'label'       => __( 'Horizontal padding (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_buttons',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 8,
				'max'  => 60,
				'step' => 1,
			],
		]
	);

	/* ---------- Layout ---------- */
	$wp_customize->add_section(
		'lienzoastra_layout',
		[
			'title' => __( 'Layout', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_container_width',
		[
			'default'           => $defaults['container_width'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_container_width',
		[
			'label'       => __( 'Site container width (px)', 'lienzo-astra' ),
			'description' => __( 'Max width for wide sections such as the header and footer.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_layout',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 960,
				'max'  => 1600,
				'step' => 10,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_content_width',
		[
			'default'           => $defaults['content_width'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_content_width',
		[
			'label'       => __( 'Content width (px)', 'lienzo-astra' ),
			'description' => __( 'Max width for the readable content column (posts, pages).', 'lienzo-astra' ),
			'section'     => 'lienzoastra_layout',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 600,
				'max'  => 1200,
				'step' => 10,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_scroll_to_top',
		[
			'default'           => $defaults['scroll_to_top'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_scroll_to_top',
		[
			'label'       => __( 'Show a scroll-to-top button', 'lienzo-astra' ),
			'description' => __( 'Adds a floating button that appears once the visitor scrolls down.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_layout',
			'type'        => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_site_layout',
		[
			'default'           => $defaults['site_layout'],
			'sanitize_callback' => 'lienzoastra_sanitize_site_layout',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_site_layout',
		[
			'label'       => __( 'Site layout', 'lienzo-astra' ),
			'description' => __( 'Boxed puts the content on a card over a contrasting page background.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_layout',
			'type'        => 'select',
			'choices'     => [
				'full'  => __( 'Full width', 'lienzo-astra' ),
				'boxed' => __( 'Boxed', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_site_boxed_bg',
		[
			'default'           => $defaults['site_boxed_bg'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_site_boxed_bg',
			[
				'label'   => __( 'Page background (boxed layout)', 'lienzo-astra' ),
				'section' => 'lienzoastra_layout',
			]
		)
	);

	/* ---------- Header ---------- */
	$wp_customize->add_section(
		'lienzoastra_header',
		[
			'title' => __( 'Header layout', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_layout',
		[
			'default'           => $defaults['header_layout'],
			'sanitize_callback' => 'lienzoastra_sanitize_header_layout',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_layout',
		[
			'label'   => __( 'Logo position', 'lienzo-astra' ),
			'section' => 'lienzoastra_header',
			'type'    => 'select',
			'choices' => [
				'left'   => __( 'Left, menu right', 'lienzo-astra' ),
				'center' => __( 'Centered logo, menu below', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_sticky',
		[
			'default'           => $defaults['header_sticky'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_sticky',
		[
			'label'   => __( 'Make header sticky on scroll', 'lienzo-astra' ),
			'section' => 'lienzoastra_header',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_logo_width',
		[
			'default'           => $defaults['logo_width'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_logo_width',
		[
			'label'       => __( 'Logo width (px)', 'lienzo-astra' ),
			'description' => __( 'Adjust the header logo size — the height scales automatically to keep its proportions. Set to 0 to use the image\'s original size.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_header',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 400,
				'step' => 5,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_logo_width_mobile',
		[
			'default'           => $defaults['logo_width_mobile'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_logo_width_mobile',
		[
			'label'       => __( 'Logo width on mobile (px)', 'lienzo-astra' ),
			'description' => __( 'Optional smaller logo below 768px. Set to 0 to reuse the desktop width.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_header',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 300,
				'step' => 5,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_full_width',
		[
			'default'           => $defaults['header_full_width'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_full_width',
		[
			'label'   => __( 'Full-width header', 'lienzo-astra' ),
			'section' => 'lienzoastra_header',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_menu_breakpoint',
		[
			'default'           => $defaults['header_menu_breakpoint'],
			'sanitize_callback' => 'lienzoastra_sanitize_menu_breakpoint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_menu_breakpoint',
		[
			'label'       => __( 'Mobile menu breakpoint', 'lienzo-astra' ),
			'description' => __( 'Below this width the horizontal menu collapses into the hamburger toggle.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_header',
			'type'        => 'select',
			'choices'     => [
				'menu-dropdown-mobile' => __( 'Mobile (under 576px)', 'lienzo-astra' ),
				'menu-dropdown-tablet' => __( 'Tablet (under 992px)', 'lienzo-astra' ),
				'menu-dropdown-none'   => __( 'Never collapse', 'lienzo-astra' ),
				'menu-layout-dropdown' => __( 'Always show hamburger', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_bg_color',
		[
			'default'           => $defaults['header_bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_header_bg_color',
			[
				'label'   => __( 'Header background', 'lienzo-astra' ),
				'section' => 'lienzoastra_header',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_header_padding',
		[
			'default'           => $defaults['header_padding'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_padding',
		[
			'label'       => __( 'Header vertical padding (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_header',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 60,
				'step' => 2,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_border',
		[
			'default'           => $defaults['header_border'],
			'sanitize_callback' => 'rest_sanitize_boolean',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_border',
		[
			'label'   => __( 'Bottom border under the header', 'lienzo-astra' ),
			'section' => 'lienzoastra_header',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_border_color',
		[
			'default'           => $defaults['header_border_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_header_border_color',
			[
				'label'   => __( 'Header border color', 'lienzo-astra' ),
				'section' => 'lienzoastra_header',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_header_shadow',
		[
			'default'           => $defaults['header_shadow'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_shadow',
		[
			'label'   => __( 'Drop shadow under the header', 'lienzo-astra' ),
			'section' => 'lienzoastra_header',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_header_transparent',
		[
			'default'           => $defaults['header_transparent'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_header_transparent',
		[
			'label'       => __( 'Transparent header', 'lienzo-astra' ),
			'description' => __( 'Overlays the header on top of the content. Works best on pages that open with a hero or cover image.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_header',
			'type'        => 'checkbox',
		]
	);

	/* ---------- Header menu ---------- */
	$wp_customize->add_section(
		'lienzoastra_menu',
		[
			'title' => __( 'Header menu', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_alignment',
		[
			'default'           => $defaults['menu_alignment'],
			'sanitize_callback' => 'lienzoastra_sanitize_menu_alignment',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_alignment',
		[
			'label'   => __( 'Menu alignment', 'lienzo-astra' ),
			'section' => 'lienzoastra_menu',
			'type'    => 'select',
			'choices' => [
				'left'   => __( 'Left', 'lienzo-astra' ),
				'center' => __( 'Center', 'lienzo-astra' ),
				'right'  => __( 'Right', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_item_spacing',
		[
			'default'           => $defaults['menu_item_spacing'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_item_spacing',
		[
			'label'       => __( 'Space between items (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_menu',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 40,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_font_size',
		[
			'default'           => $defaults['menu_font_size'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_font_size',
		[
			'label'       => __( 'Menu font size (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_menu',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 12,
				'max'  => 22,
				'step' => 1,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_font_weight',
		[
			'default'           => $defaults['menu_font_weight'],
			'sanitize_callback' => 'lienzoastra_sanitize_font_weight',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_font_weight',
		[
			'label'   => __( 'Menu font weight', 'lienzo-astra' ),
			'section' => 'lienzoastra_menu',
			'type'    => 'select',
			'choices' => [
				'400' => __( 'Regular (400)', 'lienzo-astra' ),
				'500' => __( 'Medium (500)', 'lienzo-astra' ),
				'600' => __( 'Semi-bold (600)', 'lienzo-astra' ),
				'700' => __( 'Bold (700)', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_text_transform',
		[
			'default'           => $defaults['menu_text_transform'],
			'sanitize_callback' => 'lienzoastra_sanitize_text_transform',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_text_transform',
		[
			'label'   => __( 'Menu text transform', 'lienzo-astra' ),
			'section' => 'lienzoastra_menu',
			'type'    => 'select',
			'choices' => [
				'none'       => __( 'None', 'lienzo-astra' ),
				'uppercase'  => __( 'UPPERCASE', 'lienzo-astra' ),
				'capitalize' => __( 'Capitalize', 'lienzo-astra' ),
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_link_color',
		[
			'default'           => $defaults['menu_link_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_menu_link_color',
			[
				'label'   => __( 'Menu link color', 'lienzo-astra' ),
				'section' => 'lienzoastra_menu',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_link_hover_color',
		[
			'default'           => $defaults['menu_link_hover_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_menu_link_hover_color',
			[
				'label'   => __( 'Menu hover/active color', 'lienzo-astra' ),
				'section' => 'lienzoastra_menu',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_toggle_color',
		[
			'default'           => $defaults['toggle_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_toggle_color',
			[
				'label'   => __( 'Hamburger icon color', 'lienzo-astra' ),
				'section' => 'lienzoastra_menu',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_dropdown_bg_color',
		[
			'default'           => $defaults['dropdown_bg_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_dropdown_bg_color',
			[
				'label'   => __( 'Mobile dropdown background', 'lienzo-astra' ),
				'section' => 'lienzoastra_menu',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_dropdown_text_color',
		[
			'default'           => $defaults['dropdown_text_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_dropdown_text_color',
			[
				'label'   => __( 'Mobile dropdown text color', 'lienzo-astra' ),
				'section' => 'lienzoastra_menu',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_menu_hover_style',
		[
			'default'           => $defaults['menu_hover_style'],
			'sanitize_callback' => 'lienzoastra_sanitize_menu_hover',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_menu_hover_style',
		[
			'label'   => __( 'Menu hover effect', 'lienzo-astra' ),
			'section' => 'lienzoastra_menu',
			'type'    => 'select',
			'choices' => [
				'color'     => __( 'Color change', 'lienzo-astra' ),
				'underline' => __( 'Animated underline', 'lienzo-astra' ),
			],
		]
	);

	/* ---------- Footer ---------- */
	$wp_customize->add_section(
		'lienzoastra_footer',
		[
			'title' => __( 'Footer', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_color_background',
		[
			'default'           => $defaults['footer_color_background'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_footer_color_background',
			[
				'label'   => __( 'Footer background', 'lienzo-astra' ),
				'section' => 'lienzoastra_footer',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_color_text',
		[
			'default'           => $defaults['footer_color_text'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_footer_color_text',
			[
				'label'   => __( 'Footer text color', 'lienzo-astra' ),
				'section' => 'lienzoastra_footer',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_link_color',
		[
			'default'           => $defaults['footer_link_color'],
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lienzoastra_footer_link_color',
			[
				'label'   => __( 'Footer link color', 'lienzo-astra' ),
				'section' => 'lienzoastra_footer',
			]
		)
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_padding',
		[
			'default'           => $defaults['footer_padding'],
			'sanitize_callback' => 'absint',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_footer_padding',
		[
			'label'       => __( 'Footer vertical padding (px)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_footer',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 80,
				'step' => 2,
			],
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_full_width',
		[
			'default'           => $defaults['footer_full_width'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_footer_full_width',
		[
			'label'   => __( 'Full-width footer', 'lienzo-astra' ),
			'section' => 'lienzoastra_footer',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_copyright',
		[
			'default'           => $defaults['footer_copyright'],
			'sanitize_callback' => 'sanitize_textarea_field',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_footer_copyright',
		[
			'label'       => __( 'Copyright text', 'lienzo-astra' ),
			'description' => __( 'Shown at the bottom of the footer. You can use [year] and [site] as placeholders. Leave empty to hide it.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_footer',
			'type'        => 'textarea',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_footer_align',
		[
			'default'           => $defaults['footer_align'],
			'sanitize_callback' => 'lienzoastra_sanitize_footer_align',
			'transport'         => 'postMessage',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_footer_align',
		[
			'label'   => __( 'Content alignment', 'lienzo-astra' ),
			'section' => 'lienzoastra_footer',
			'type'    => 'select',
			'choices' => [
				'left'   => __( 'Left', 'lienzo-astra' ),
				'center' => __( 'Center', 'lienzo-astra' ),
				'right'  => __( 'Right', 'lienzo-astra' ),
			],
		]
	);

	/* ---------- Blog ---------- */
	$wp_customize->add_section(
		'lienzoastra_blog',
		[
			'title' => __( 'Blog', 'lienzo-astra' ),
			'panel' => 'lienzoastra_design_options',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_blog_thumbnails',
		[
			'default'           => $defaults['blog_thumbnails'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_blog_thumbnails',
		[
			'label'   => __( 'Show featured images on archives', 'lienzo-astra' ),
			'section' => 'lienzoastra_blog',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_blog_post_meta',
		[
			'default'           => $defaults['blog_post_meta'],
			'sanitize_callback' => 'rest_sanitize_boolean',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_blog_post_meta',
		[
			'label'   => __( 'Show post meta (date, author, categories)', 'lienzo-astra' ),
			'section' => 'lienzoastra_blog',
			'type'    => 'checkbox',
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_blog_excerpt_length',
		[
			'default'           => $defaults['blog_excerpt_length'],
			'sanitize_callback' => 'absint',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_blog_excerpt_length',
		[
			'label'       => __( 'Excerpt length (words)', 'lienzo-astra' ),
			'section'     => 'lienzoastra_blog',
			'type'        => 'range',
			'input_attrs' => [
				'min'  => 0,
				'max'  => 80,
				'step' => 5,
			],
		]
	);

	/* ---------- ARC imported template ---------- */
	$wp_customize->add_section(
		'lienzoastra_arc_template',
		[
			'title'       => __( 'ARC Template', 'lienzo-astra' ),
			'description' => __( 'Palette and copy overrides applied to the imported starter-template pages.', 'lienzo-astra' ),
			'priority'    => 46,
		]
	);

	$wp_customize->add_setting(
		'lienzoastra_arc_tpl_palette',
		[
			'default'           => $defaults['arc_tpl_palette'],
			'sanitize_callback' => 'lienzoastra_sanitize_arc_palette',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_arc_tpl_palette',
		[
			'label'       => __( 'Template color palette', 'lienzo-astra' ),
			'description' => __( 'Pick a palette — the imported template updates its colors automatically. "Custom" uses the pickers below.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_arc_template',
			'type'        => 'select',
			'choices'     => [
				'default'   => __( 'Template default (ARC)', 'lienzo-astra' ),
				'arc'       => __( 'ARC River Systems', 'lienzo-astra' ),
				'corporate' => __( 'Corporate Blue', 'lienzo-astra' ),
				'ocean'     => __( 'Ocean Teal', 'lienzo-astra' ),
				'sunset'    => __( 'Sunset', 'lienzo-astra' ),
				'mono'      => __( 'Monochrome', 'lienzo-astra' ),
				'custom'    => __( 'Custom…', 'lienzo-astra' ),
			],
		]
	);

	$arc_color_controls = [
		'brand'     => __( 'Brand color', 'lienzo-astra' ),
		'brand_dark' => __( 'Brand dark', 'lienzo-astra' ),
		'accent'    => __( 'Accent', 'lienzo-astra' ),
		'navy'      => __( 'Headings / dark', 'lienzo-astra' ),
		'mint'      => __( 'Light background', 'lienzo-astra' ),
		'cream'     => __( 'Alt background', 'lienzo-astra' ),
		'cta_bg'    => __( 'CTA button background', 'lienzo-astra' ),
		'cta_text'  => __( 'CTA button text', 'lienzo-astra' ),
	];
	foreach ( $arc_color_controls as $key => $label ) {
		$wp_customize->add_setting(
			'lienzoastra_arc_tpl_color_' . $key,
			[
				'default'           => $defaults[ 'arc_tpl_color_' . $key ],
				'sanitize_callback' => 'sanitize_hex_color',
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'lienzoastra_arc_tpl_color_' . $key,
				[
					'label'           => $label,
					'section'         => 'lienzoastra_arc_template',
					'active_callback' => function () {
						return 'custom' === lienzoastra_get_option( 'arc_tpl_palette' );
					},
				]
			)
		);
	}

	$wp_customize->add_setting(
		'lienzoastra_arc_tpl_copy_md',
		[
			'default'           => $defaults['arc_tpl_copy_md'],
			'sanitize_callback' => 'lienzoastra_sanitize_copy_md',
		]
	);
	$wp_customize->add_control(
		'lienzoastra_arc_tpl_copy_md',
		[
			'label'       => __( 'Copy document (.md)', 'lienzo-astra' ),
			'description' => __( 'Choose a .md file or paste the markdown — same structure as the ARC baseline (# PAGE / ## Section / ### headings / **CTA:** lines). Texts that match the baseline are replaced on the imported pages.', 'lienzo-astra' ),
			'section'     => 'lienzoastra_arc_template',
			'type'        => 'textarea',
			'input_attrs' => [ 'rows' => 14 ],
		]
	);

	/* ---------- Button / CTA destinations (ARC Careers portals or custom) ---------- */
	$arc_cta_labels = [
		'primary'   => __( 'Primary CTA buttons', 'lienzo-astra' ),
		'partner'   => __( 'Partner / Foundation buttons', 'lienzo-astra' ),
		'secondary' => __( 'Secondary buttons', 'lienzo-astra' ),
	];
	foreach ( $arc_cta_labels as $kind => $label ) {
		$wp_customize->add_setting(
			'lienzoastra_arc_tpl_link_' . $kind,
			[
				'default'           => $defaults[ 'arc_tpl_link_' . $kind ],
				'sanitize_callback' => 'lienzoastra_sanitize_arc_link',
			]
		);
		$wp_customize->add_control(
			'lienzoastra_arc_tpl_link_' . $kind,
			[
				'label'       => $label,
				'description' => 'primary' === $kind ? __( 'Where template buttons and CTAs link to. Portal pages come from the ARC Careers plugin.', 'lienzo-astra' ) : '',
				'section'     => 'lienzoastra_arc_template',
				'type'        => 'select',
				'choices'     => lienzo_arc_cta_choices(),
			]
		);
		$wp_customize->add_setting(
			'lienzoastra_arc_tpl_link_' . $kind . '_url',
			[
				'default'           => $defaults[ 'arc_tpl_link_' . $kind . '_url' ],
				'sanitize_callback' => 'esc_url_raw',
			]
		);
		$wp_customize->add_control(
			'lienzoastra_arc_tpl_link_' . $kind . '_url',
			[
				'label'           => __( 'Custom URL', 'lienzo-astra' ),
				'section'         => 'lienzoastra_arc_template',
				'type'            => 'url',
				'active_callback' => function () use ( $kind ) {
					return 'custom' === lienzoastra_get_option( 'arc_tpl_link_' . $kind );
				},
			]
		);
	}
}
add_action( 'customize_register', 'lienzoastra_customize_register' );

/**
 * Enqueues the file-picker helper for the ARC copy markdown control.
 * Reads the chosen .md in the browser and loads it into the setting —
 * no Media Library round-trip needed.
 *
 * @return void
 */
function lienzoastra_customize_controls_scripts() {
	wp_enqueue_script(
		'lienzo-arc-copy-upload',
		get_template_directory_uri() . '/assets/js/arc-copy-upload.js',
		[ 'customize-controls' ],
		LIENZOASTRA_VERSION,
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'lienzoastra_customize_controls_scripts' );
/**
 * Live-preview support: load the small preview script only inside the
 * Customizer's live-preview iframe, for settings using postMessage.
 *
 * @return void
 */
function lienzoastra_customize_preview_js() {
	wp_enqueue_script(
		'lienzoastra-customizer-preview',
		LIENZOASTRA_URI . '/assets/js/customizer-preview.js',
		[ 'customize-preview' ],
		LIENZOASTRA_VERSION,
		true
	);

	$palettes = [];
	foreach ( lienzoastra_color_palettes() as $slug => $palette ) {
		$palettes[ $slug ] = $palette['colors'];
	}
	wp_localize_script( 'lienzoastra-customizer-preview', 'lienzoastraPalettes', $palettes );
}
add_action( 'customize_preview_init', 'lienzoastra_customize_preview_js' );
