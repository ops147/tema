<?php
/**
 * Design Options — an Astra-inspired Customizer panel.
 *
 * Adds native WordPress Customizer controls for colors, typography,
 * container width and header/footer layout, then prints the matching
 * CSS custom properties on the front end. This is original code written
 * for Lienzo Astra; it does not reuse any Astra files.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Font choices shown in the Typography section.
 *
 * 'system' uses a native font stack (no external request). Every other
 * key is fetched from Google Fonts, mirroring how Astra offers a
 * ready-made font list.
 *
 * @return array<string,string>
 */
function lienzoastra_font_choices() {
	return [
		'system'          => __( 'System default', 'lienzo-astra' ),
		'Inter'           => 'Inter',
		'Roboto'          => 'Roboto',
		'Open Sans'       => 'Open Sans',
		'Poppins'         => 'Poppins',
		'Montserrat'      => 'Montserrat',
		'Lato'            => 'Lato',
		'Merriweather'    => 'Merriweather',
		'Playfair Display' => 'Playfair Display',
	];
}

/**
 * Ready-made color palettes, in the spirit of Kadence's global palettes:
 * picking one fills the four color settings below at once.
 *
 * @return array<string,array{label:string,colors:array<string,string>}>
 */
function lienzoastra_color_palettes() {
	return [
		'default' => [
			'label'  => __( 'Default (blue)', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#2563eb',
				'color_link'       => '#2563eb',
				'color_text'       => '#1e1e1e',
				'color_background' => '#ffffff',
			],
		],
		'ocean'   => [
			'label'  => __( 'Ocean', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#0e7490',
				'color_link'       => '#0e7490',
				'color_text'       => '#164e63',
				'color_background' => '#f0f9ff',
			],
		],
		'forest'  => [
			'label'  => __( 'Forest', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#15803d',
				'color_link'       => '#15803d',
				'color_text'       => '#14532d',
				'color_background' => '#f0fdf4',
			],
		],
		'sunset'  => [
			'label'  => __( 'Sunset', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#ea580c',
				'color_link'       => '#c2410c',
				'color_text'       => '#431407',
				'color_background' => '#fff7ed',
			],
		],
		'berry'   => [
			'label'  => __( 'Berry', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#be185d',
				'color_link'       => '#be185d',
				'color_text'       => '#500724',
				'color_background' => '#fdf2f8',
			],
		],
		'slate'   => [
			'label'  => __( 'Slate', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#334155',
				'color_link'       => '#475569',
				'color_text'       => '#0f172a',
				'color_background' => '#f8fafc',
			],
		],
	];
}

/**
 * Defaults for every setting, kept in one place so the Customizer and the
 * CSS-output function never disagree.
 *
 * @return array<string,mixed>
 */
function lienzoastra_customizer_defaults() {
	return [
		'color_palette'      => 'default',
		'color_primary'      => '#2563eb',
		'color_link'         => '#2563eb',
		'color_link_hover'   => '',
		'color_text'         => '#1e1e1e',
		'color_heading'      => '',
		'color_background'   => '#ffffff',
		'font_family_body'   => 'system',
		'font_family_heading' => 'system',
		'font_size_base'     => 16,
		'line_height_base'   => 1.6,
		'container_width'    => 1200,
		'content_width'      => 800,
		'scroll_to_top'      => false,
		'header_layout'      => 'left',
		'header_sticky'      => false,
		'header_full_width'  => false,
		'logo_width'         => 0,
		'logo_width_mobile'  => 0,
		'header_bg_color'    => '',
		'header_padding'     => 16,
		'header_border'      => false,
		'header_border_color' => '#e5e7eb',
		'header_menu_breakpoint' => 'menu-dropdown-tablet',
		'menu_alignment'     => 'right',
		'menu_item_spacing'  => 6,
		'menu_font_size'     => 15,
		'menu_font_weight'   => '500',
		'menu_text_transform' => 'none',
		'menu_link_color'    => '',
		'menu_link_hover_color' => '',
		'toggle_color'       => '',
		'dropdown_bg_color'  => '',
		'dropdown_text_color' => '',
		'footer_color_background' => '#1e1e1e',
		'footer_color_text'  => '#ffffff',
		'footer_link_color'  => '',
		'footer_padding'     => 16,
		'footer_full_width'  => false,
		'footer_copyright'   => '',
		'blog_thumbnails'    => true,
		'blog_post_meta'     => true,
		'blog_excerpt_length' => 30,
		'shop_columns'       => 4,
		'shop_per_page'      => 12,
		'header_cart'        => true,
		'arc_tpl_palette'    => 'default',
		'arc_tpl_color_brand' => '',
		'arc_tpl_color_brand_dark' => '',
		'arc_tpl_color_accent' => '',
		'arc_tpl_color_navy' => '',
		'arc_tpl_color_mint' => '',
		'arc_tpl_color_cream' => '',
		'arc_tpl_color_cta_bg' => '',
		'arc_tpl_color_cta_text' => '',
		'arc_tpl_copy_md'    => '',
	];
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
				'arc'       => __( 'ARC Green', 'lienzo-astra' ),
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
 * Whitelist sanitizer for the font-choice selects.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_font_choice( $input ) {
	$choices = array_keys( lienzoastra_font_choices() );

	return in_array( $input, $choices, true ) ? $input : 'system';
}

/**
 * Whitelist sanitizer for the header layout select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_header_layout( $input ) {
	return in_array( $input, [ 'left', 'center' ], true ) ? $input : 'left';
}

/**
 * Whitelist sanitizer for the preset-palette select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_palette( $input ) {
	$palettes = lienzoastra_color_palettes();

	return isset( $palettes[ $input ] ) ? $input : 'default';
}

/**
 * Whitelist sanitizer for the ARC template palette select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_arc_palette( $input ) {
	$choices = array( 'default', 'custom' ) + lienzo_arc_palette_presets();

	return isset( $choices[ $input ] ) ? $input : 'default';
}

/**
 * Keeps the pasted copy markdown as plain text — strips HTML tags (which a
 * copy document shouldn't carry) without mangling markdown characters or
 * entities, since pairing relies on exact baseline matches.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_copy_md( $input ) {
	return preg_replace( '/<\/?[a-z][^>]*>/i', '', (string) $input );
}

/**
 * Whitelist sanitizer for the mobile-menu breakpoint select. The values
 * are the class names the header CSS already understands.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_menu_breakpoint( $input ) {
	$choices = [ 'menu-dropdown-mobile', 'menu-dropdown-tablet', 'menu-dropdown-none', 'menu-layout-dropdown' ];

	return in_array( $input, $choices, true ) ? $input : 'menu-dropdown-tablet';
}

/**
 * Whitelist sanitizer for the menu alignment select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_menu_alignment( $input ) {
	return in_array( $input, [ 'left', 'center', 'right' ], true ) ? $input : 'right';
}

/**
 * Whitelist sanitizer for the menu font-weight select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_font_weight( $input ) {
	$input = (string) $input;

	return in_array( $input, [ '400', '500', '600', '700' ], true ) ? $input : '500';
}

/**
 * Whitelist sanitizer for the menu text-transform select.
 *
 * @param string $input Submitted value.
 *
 * @return string
 */
function lienzoastra_sanitize_text_transform( $input ) {
	return in_array( $input, [ 'none', 'uppercase', 'capitalize' ], true ) ? $input : 'none';
}

/**
 * Clamp the body line-height slider to its control range.
 *
 * @param mixed $input Submitted value.
 *
 * @return float
 */
function lienzoastra_sanitize_line_height( $input ) {
	$value = (float) $input;

	return min( 2.2, max( 1.2, $value ) );
}

/**
 * Read a Design Options value, falling back to its default.
 *
 * @param string $key Key from lienzoastra_customizer_defaults(), without prefix.
 *
 * @return mixed
 */
function lienzoastra_get_option( $key ) {
	$defaults = lienzoastra_customizer_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';

	return get_theme_mod( 'lienzoastra_' . $key, $default );
}

/**
 * Google Fonts stylesheet URL for any non-system font in use, or an empty
 * string when both font choices are the system stack.
 *
 * @return string
 */
function lienzoastra_google_fonts_url() {
	$body    = lienzoastra_get_option( 'font_family_body' );
	$heading = lienzoastra_get_option( 'font_family_heading' );

	$families = array_unique( array_filter( [ $body, $heading ], function ( $font ) {
		return 'system' !== $font;
	} ) );

	if ( empty( $families ) ) {
		return '';
	}

	$query = [];
	foreach ( $families as $family ) {
		$query[] = str_replace( ' ', '+', $family ) . ':wght@400;600;700';
	}

	return add_query_arg(
		[
			'family'  => implode( '&family=', $query ),
			'display' => 'swap',
		],
		'https://fonts.googleapis.com/css2'
	);
}

/**
 * Enqueue a Google Fonts stylesheet for any non-system font in use.
 *
 * @return void
 */
function lienzoastra_enqueue_google_fonts() {
	if ( lienzo_is_arc_portal_page() ) {
		return; // ARC Careers portals bypass the theme — never restyle them.
	}

	$src = lienzoastra_google_fonts_url();

	if ( '' === $src ) {
		return;
	}

	wp_enqueue_style( 'lienzoastra-google-fonts', $src, [], null );
}
add_action( 'wp_enqueue_scripts', 'lienzoastra_enqueue_google_fonts' );

/**
 * Warm up the connection to the Google Fonts servers so the font
 * stylesheet (and then the font files) arrives sooner.
 *
 * @param array  $urls          URLs hinted for the given relation.
 * @param string $relation_type Resource hint type (preconnect, dns-prefetch…).
 *
 * @return array
 */
function lienzoastra_fonts_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type || '' === lienzoastra_google_fonts_url() ) {
		return $urls;
	}

	$urls[] = 'https://fonts.googleapis.com';
	$urls[] = [
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => true,
	];

	return $urls;
}
add_filter( 'wp_resource_hints', 'lienzoastra_fonts_resource_hints', 10, 2 );

/**
 * Build the `font-family` value for a Design Options font choice.
 *
 * @param string $font Chosen font key.
 *
 * @return string
 */
function lienzoastra_font_stack( $font ) {
	if ( 'system' === $font ) {
		return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
	}

	return '"' . $font . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
}

/**
 * Print the Design Options as CSS custom properties plus the small set of
 * rules that consume them, right after the theme's own stylesheets.
 *
 * @return void
 */
function lienzoastra_customizer_css() {
	if ( lienzo_is_arc_portal_page() || ! apply_filters( 'lienzoastra_design_options', true ) ) {
		return;
	}

	$primary          = lienzoastra_get_option( 'color_primary' );
	$link             = lienzoastra_get_option( 'color_link' );
	$link_hover       = lienzoastra_get_option( 'color_link_hover' );
	$text             = lienzoastra_get_option( 'color_text' );
	$heading          = lienzoastra_get_option( 'color_heading' );
	$background       = lienzoastra_get_option( 'color_background' );
	$font_body        = lienzoastra_font_stack( lienzoastra_get_option( 'font_family_body' ) );
	$font_heading     = lienzoastra_font_stack( lienzoastra_get_option( 'font_family_heading' ) );
	$font_size        = (int) lienzoastra_get_option( 'font_size_base' );
	$line_height      = (float) lienzoastra_get_option( 'line_height_base' );
	$container_width  = (int) lienzoastra_get_option( 'container_width' );
	$content_width    = (int) lienzoastra_get_option( 'content_width' );
	$header_layout    = lienzoastra_get_option( 'header_layout' );
	$header_sticky    = lienzoastra_get_option( 'header_sticky' );

	ob_start();
	?>
	:root {
		--lienzoastra-color-primary: <?php echo esc_html( $primary ); ?>;
		--lienzoastra-color-link: <?php echo esc_html( $link ); ?>;
		--lienzoastra-color-link-hover: <?php echo esc_html( '' !== $link_hover ? $link_hover : $link ); ?>;
		--lienzoastra-color-text: <?php echo esc_html( $text ); ?>;
		--lienzoastra-color-heading: <?php echo esc_html( '' !== $heading ? $heading : $text ); ?>;
		--lienzoastra-color-background: <?php echo esc_html( $background ); ?>;
		--lienzoastra-font-body: <?php echo $font_body; // Font stack — whitelisted choice, esc_html would break quotes. ?>;
		--lienzoastra-font-heading: <?php echo $font_heading; ?>;
		--lienzoastra-font-size-base: <?php echo (int) $font_size; ?>px;
		--lienzoastra-line-height: <?php echo esc_html( $line_height ); ?>;
		--lienzoastra-container-width: <?php echo (int) $container_width; ?>px;
		--lienzoastra-content-width: <?php echo (int) $content_width; ?>px;
	}

	body {
		background-color: var(--lienzoastra-color-background);
		color: var(--lienzoastra-color-text);
		font-family: var(--lienzoastra-font-body);
		font-size: var(--lienzoastra-font-size-base);
		line-height: var(--lienzoastra-line-height);
	}

	a { color: var(--lienzoastra-color-link); }
	a:hover, a:focus { color: var(--lienzoastra-color-link-hover); }

	h1, h2, h3, h4, h5, h6,
	.entry-title, .site-title {
		color: var(--lienzoastra-color-heading);
		font-family: var(--lienzoastra-font-heading);
	}

	.site-header .header-inner,
	.site-footer .footer-inner,
	.site-header:not(.header-full-width),
	.site-footer:not(.footer-full-width),
	body:not([class*="elementor-page-"]) .site-main,
	.page-header .entry-title {
		max-width: var(--lienzoastra-container-width);
	}

	@media (min-width: 992px) {
		body:not([class*="elementor-page-"]) .site-main,
		.page-header .entry-title {
			max-width: var(--lienzoastra-content-width);
		}
	}

	<?php if ( 'center' === $header_layout ) : ?>
	.site-header .header-inner {
		align-items: center;
		flex-direction: column;
		text-align: center;
	}
	<?php endif; ?>

	<?php if ( $header_sticky ) : ?>
	.site-header {
		left: 0;
		position: sticky;
		top: 0;
		z-index: 999;
	}
	<?php endif; ?>
	<?php

	$css = (string) ob_get_clean();

	wp_register_style( 'lienzoastra-design-options', false, [ 'lienzo-theme' ], LIENZOASTRA_VERSION );
	wp_enqueue_style( 'lienzoastra-design-options' );
	wp_add_inline_style( 'lienzoastra-design-options', $css );

	// The chrome sheet loads on every page that renders the header, so the
	// header/footer/menu rules keep working even where the design-options
	// sheet doesn't print (e.g. ARC starter pages, whose dependency
	// lienzo-theme is skipped).
	if ( wp_style_is( 'lienzo-header-footer', 'enqueued' ) ) {
		wp_add_inline_style( 'lienzo-header-footer', lienzoastra_chrome_css() );
	}
}
add_action( 'wp_enqueue_scripts', 'lienzoastra_customizer_css', 20 );

/**
 * Build the header/footer/menu CSS for the current Design Options values.
 * Printed inline on the chrome stylesheet (lienzo-header-footer).
 *
 * @return string
 */
function lienzoastra_chrome_css() {
	$align_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	$logo_width        = (int) lienzoastra_get_option( 'logo_width' );
	$logo_width_mobile = (int) lienzoastra_get_option( 'logo_width_mobile' );
	$header_bg         = lienzoastra_get_option( 'header_bg_color' );
	$header_padding    = (int) lienzoastra_get_option( 'header_padding' );
	$header_border     = lienzoastra_get_option( 'header_border' );
	$header_border_col = lienzoastra_get_option( 'header_border_color' );
	$menu_align        = lienzoastra_get_option( 'menu_alignment' );
	$menu_gap          = (int) lienzoastra_get_option( 'menu_item_spacing' );
	$menu_font_size    = (int) lienzoastra_get_option( 'menu_font_size' );
	$menu_font_weight  = lienzoastra_get_option( 'menu_font_weight' );
	$menu_transform    = lienzoastra_get_option( 'menu_text_transform' );
	$menu_color        = lienzoastra_get_option( 'menu_link_color' );
	$menu_hover        = lienzoastra_get_option( 'menu_link_hover_color' );
	$toggle_color      = lienzoastra_get_option( 'toggle_color' );
	$dropdown_bg       = lienzoastra_get_option( 'dropdown_bg_color' );
	$dropdown_text     = lienzoastra_get_option( 'dropdown_text_color' );
	$footer_bg         = lienzoastra_get_option( 'footer_color_background' );
	$footer_text       = lienzoastra_get_option( 'footer_color_text' );
	$footer_link       = lienzoastra_get_option( 'footer_link_color' );
	$footer_padding    = (int) lienzoastra_get_option( 'footer_padding' );
	$scroll_to_top     = lienzoastra_get_option( 'scroll_to_top' );

	ob_start();
	?>
	:root {
		--lienzoastra-header-bg: <?php echo esc_html( '' !== $header_bg ? $header_bg : 'transparent' ); ?>;
		--lienzoastra-header-padding: <?php echo (int) $header_padding; ?>px;
		--lienzoastra-header-border-width: <?php echo $header_border ? '1px' : '0'; ?>;
		--lienzoastra-header-border-color: <?php echo esc_html( '' !== $header_border_col ? $header_border_col : '#e5e7eb' ); ?>;
		--lienzoastra-menu-align: <?php echo esc_html( isset( $align_map[ $menu_align ] ) ? $align_map[ $menu_align ] : 'flex-end' ); ?>;
		--lienzoastra-menu-gap: <?php echo (int) $menu_gap; ?>px;
		--lienzoastra-menu-font-size: <?php echo (int) $menu_font_size; ?>px;
		--lienzoastra-menu-font-weight: <?php echo esc_html( $menu_font_weight ); ?>;
		--lienzoastra-menu-text-transform: <?php echo esc_html( $menu_transform ); ?>;
		--lienzoastra-menu-color: <?php echo esc_html( '' !== $menu_color ? $menu_color : 'inherit' ); ?>;
		--lienzoastra-menu-color-hover: <?php echo esc_html( '' !== $menu_hover ? $menu_hover : 'var(--color-brand, var(--lienzoastra-color-link, #2563eb))' ); ?>;
		--lienzoastra-toggle-color: <?php echo esc_html( '' !== $toggle_color ? $toggle_color : 'inherit' ); ?>;
		--lienzoastra-dropdown-bg: <?php echo esc_html( '' !== $dropdown_bg ? $dropdown_bg : '#ffffff' ); ?>;
		--lienzoastra-dropdown-color: <?php echo esc_html( '' !== $dropdown_text ? $dropdown_text : 'inherit' ); ?>;
		--lienzoastra-footer-bg: <?php echo esc_html( $footer_bg ); ?>;
		--lienzoastra-footer-text: <?php echo esc_html( $footer_text ); ?>;
		--lienzoastra-footer-link: <?php echo esc_html( '' !== $footer_link ? $footer_link : 'var(--lienzoastra-footer-text)' ); ?>;
		--lienzoastra-footer-padding: <?php echo (int) $footer_padding; ?>px;
		<?php if ( $logo_width > 0 ) : ?>
		--lienzoastra-logo-width: <?php echo (int) $logo_width; ?>px;
		<?php endif; ?>
	}

	.site-header {
		background-color: var(--lienzoastra-header-bg);
		border-bottom: var(--lienzoastra-header-border-width) solid var(--lienzoastra-header-border-color);
		padding-block-end: var(--lienzoastra-header-padding);
		padding-block-start: var(--lienzoastra-header-padding);
	}

	.site-header .site-navigation {
		justify-content: var(--lienzoastra-menu-align);
	}

	.site-header .site-navigation ul.menu {
		column-gap: var(--lienzoastra-menu-gap);
		justify-content: var(--lienzoastra-menu-align);
	}

	.site-header .site-navigation ul.menu > li > a {
		font-size: var(--lienzoastra-menu-font-size);
		font-weight: var(--lienzoastra-menu-font-weight);
		text-transform: var(--lienzoastra-menu-text-transform);
	}

	.site-header .site-navigation ul.menu li a {
		color: var(--lienzoastra-menu-color);
	}

	.site-header .site-navigation ul.menu > li:hover > a,
	.site-header .site-navigation ul.menu > li.current-menu-item > a,
	.site-header .site-navigation ul.menu > li.current-menu-ancestor > a {
		color: var(--lienzoastra-menu-color-hover);
	}

	.site-navigation-toggle-holder .site-navigation-toggle {
		color: var(--lienzoastra-toggle-color);
	}

	.site-navigation-dropdown ul.menu,
	.site-navigation-dropdown ul.menu li a {
		background: var(--lienzoastra-dropdown-bg);
	}

	.site-navigation-dropdown ul.menu li a {
		color: var(--lienzoastra-dropdown-color);
	}

	<?php
	// Imported ARC templates type their copy through Tailwind tokens
	// (--font-sans / --font-display) declared in the page markup — those
	// utility classes outrank body/h1-h6 font rules. Redefining the tokens
	// on the template wrapper lets a Customizer font reach inside. This
	// lives in chrome_css because the design-options sheet isn't printed on
	// ARC template pages (its lienzo-theme dependency is skipped there).
	// "system" is skipped so the template's bundled fonts stay untouched.
	$arc_font_body    = lienzoastra_get_option( 'font_family_body' );
	$arc_font_heading = lienzoastra_get_option( 'font_family_heading' );
	if ( 'system' !== $arc_font_body || 'system' !== $arc_font_heading ) :
		?>
	body.arc-tpl, .arc-tpl {
		<?php if ( 'system' !== $arc_font_body ) : ?>
		--font-sans: <?php echo lienzoastra_font_stack( $arc_font_body ); ?>;
		<?php endif; ?>
		<?php if ( 'system' !== $arc_font_heading ) : ?>
		--font-display: <?php echo lienzoastra_font_stack( $arc_font_heading ); ?>;
		<?php endif; ?>
	}
	<?php endif; ?>

	<?php echo lienzo_arc_palette_css(); // Palette overrides for imported template pages. ?>

	.site-footer {
		background-color: var(--lienzoastra-footer-bg);
		color: var(--lienzoastra-footer-text);
		padding-block-end: var(--lienzoastra-footer-padding);
		padding-block-start: var(--lienzoastra-footer-padding);
	}

	.site-footer a { color: var(--lienzoastra-footer-link); }

	.site-footer .copyright {
		margin-block-start: .5rem;
		text-align: center;
	}
	.site-footer .copyright p { margin: 0; }

	<?php if ( $logo_width_mobile > 0 ) : ?>
	@media (max-width: 767px) {
		.site-header .site-branding .custom-logo {
			max-width: <?php echo (int) $logo_width_mobile; ?>px;
			width: <?php echo (int) $logo_width_mobile; ?>px;
		}
	}
	<?php endif; ?>

	<?php if ( $scroll_to_top ) : ?>
	.lienzoastra-scroll-top {
		align-items: center;
		background: var(--lienzoastra-color-primary, #2563eb);
		border: 0;
		border-radius: 50%;
		bottom: 1.5rem;
		color: #fff;
		cursor: pointer;
		display: flex;
		height: 44px;
		inset-inline-end: 1.5rem;
		justify-content: center;
		opacity: 0;
		padding: 0;
		pointer-events: none;
		position: fixed;
		transform: translateY(10px);
		transition: opacity .2s ease, transform .2s ease;
		width: 44px;
		z-index: 9999;
	}
	.lienzoastra-scroll-top.lienzoastra-visible {
		opacity: 1;
		pointer-events: auto;
		transform: none;
	}
	<?php endif; ?>
	<?php

	return (string) ob_get_clean();
}

/**
 * Apply the Customizer's mobile-menu breakpoint to the static header by
 * answering the class filter the header template exposes.
 *
 * @param string $class Default breakpoint class.
 *
 * @return string
 */
function lienzoastra_static_header_breakpoint( $class ) {
	$breakpoint = lienzoastra_get_option( 'header_menu_breakpoint' );

	return '' !== $breakpoint ? $breakpoint : $class;
}
add_filter( 'lienzo_static_header_menu_dropdown', 'lienzoastra_static_header_breakpoint' );

/**
 * Print the floating scroll-to-top button when the option is enabled.
 *
 * @return void
 */
function lienzoastra_scroll_to_top_button() {
	if ( ! lienzoastra_get_option( 'scroll_to_top' ) || lienzo_is_arc_portal_page() ) {
		return;
	}
	?>
	<button type="button" class="lienzoastra-scroll-top" aria-label="<?php echo esc_attr__( 'Scroll to top', 'lienzo-astra' ); ?>">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
	</button>
	<?php
}
add_action( 'wp_footer', 'lienzoastra_scroll_to_top_button' );

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

/**
 * Keep the block editor's global palette in sync with Design Options, so
 * blocks and patterns using the theme colors follow the Customizer the
 * same way Kadence's global palette does.
 *
 * @param WP_Theme_JSON_Data $theme_json Theme JSON data object.
 *
 * @return WP_Theme_JSON_Data
 */
function lienzoastra_sync_block_palette( $theme_json ) {
	if ( ! apply_filters( 'lienzoastra_sync_block_palette', true ) ) {
		return $theme_json;
	}

	$palette = [
		[
			'slug'  => 'primary',
			'color' => lienzoastra_get_option( 'color_primary' ),
			'name'  => __( 'Primary', 'lienzo-astra' ),
		],
		[
			'slug'  => 'secondary',
			'color' => lienzoastra_get_option( 'color_text' ),
			'name'  => __( 'Secondary', 'lienzo-astra' ),
		],
		[
			'slug'  => 'surface',
			'color' => '#f5f5f5',
			'name'  => __( 'Surface', 'lienzo-astra' ),
		],
		[
			'slug'  => 'dark',
			'color' => '#121212',
			'name'  => __( 'Dark', 'lienzo-astra' ),
		],
		[
			'slug'  => 'light',
			'color' => lienzoastra_get_option( 'color_background' ),
			'name'  => __( 'Light', 'lienzo-astra' ),
		],
	];

	// update_with() replaces the whole preset list for this origin — merge
	// back any presets other plugins added (e.g. ARC Starter Templates'
	// demo palette) so the theme never clobbers foreign presets.
	$theme_slugs = wp_list_pluck( $palette, 'slug' );
	$existing    = $theme_json->get_data();
	$current     = isset( $existing['settings']['color']['palette'] ) ? (array) $existing['settings']['color']['palette'] : [];
	if ( isset( $current['theme'] ) && is_array( $current['theme'] ) ) {
		$current = $current['theme'];
	}
	foreach ( $current as $preset ) {
		if ( isset( $preset['slug'], $preset['color'] ) && ! in_array( $preset['slug'], $theme_slugs, true ) ) {
			$palette[] = $preset;
		}
	}

	return $theme_json->update_with(
		[
			'version'  => 3,
			'settings' => [
				'color' => [
					'palette' => $palette,
				],
			],
		]
	);
}
add_filter( 'wp_theme_json_data_theme', 'lienzoastra_sync_block_palette' );

/**
 * Give the block editor the same typography, colors and Google Fonts the
 * front end gets, so writing in Gutenberg feels like editing the site
 * itself (Blocksy-style WYSIWYG parity).
 *
 * @return void
 */
function lienzoastra_block_editor_assets() {
	$fonts_url = lienzoastra_google_fonts_url();
	$handle    = 'lienzoastra-editor';

	if ( '' !== $fonts_url ) {
		wp_enqueue_style( $handle, $fonts_url, [], null );
	} else {
		wp_register_style( $handle, false, [], LIENZOASTRA_VERSION );
		wp_enqueue_style( $handle );
	}

	$arc_font_body    = lienzoastra_get_option( 'font_family_body' );
	$arc_font_heading = lienzoastra_get_option( 'font_family_heading' );

	$css = sprintf(
		'.editor-styles-wrapper { background-color: %1$s; color: %2$s; font-family: %3$s; font-size: %4$dpx; }
		.editor-styles-wrapper a { color: %5$s; }
		.editor-styles-wrapper h1, .editor-styles-wrapper h2, .editor-styles-wrapper h3,
		.editor-styles-wrapper h4, .editor-styles-wrapper h5, .editor-styles-wrapper h6 { font-family: %6$s; }',
		esc_html( lienzoastra_get_option( 'color_background' ) ),
		esc_html( lienzoastra_get_option( 'color_text' ) ),
		lienzoastra_font_stack( $arc_font_body ), // Whitelisted font choice — esc_html would break quotes.
		(int) lienzoastra_get_option( 'font_size_base' ),
		esc_html( lienzoastra_get_option( 'color_link' ) ),
		lienzoastra_font_stack( $arc_font_heading )
	);

	// Same bridge as the front end: imported ARC templates set --font-sans /
	// --font-display in their markup, so a Customizer font only reaches the
	// template copy if the tokens are redefined on the wrapper.
	if ( 'system' !== $arc_font_body || 'system' !== $arc_font_heading ) {
		$bridge = '.editor-styles-wrapper .arc-tpl {';
		if ( 'system' !== $arc_font_body ) {
			$bridge .= ' --font-sans: ' . lienzoastra_font_stack( $arc_font_body ) . ';';
		}
		if ( 'system' !== $arc_font_heading ) {
			$bridge .= ' --font-display: ' . lienzoastra_font_stack( $arc_font_heading ) . ';';
		}
		$css .= $bridge . ' }';
	}

	wp_add_inline_style( $handle, $css );
}
add_action( 'enqueue_block_editor_assets', 'lienzoastra_block_editor_assets' );
