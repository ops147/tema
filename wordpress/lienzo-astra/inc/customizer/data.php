<?php
/**
 * Customizer — font choices, palettes, defaults and the option reader.
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
		'arc'     => [
			'label'  => __( 'ARC River Systems', 'lienzo-astra' ),
			'colors' => [
				'color_primary'    => '#59B8A9',
				'color_link'       => '#3E8E80',
				'color_text'       => '#53616B',
				'color_background' => '#F5F8F6',
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
		'dark_bg_color'      => '',
		'dark_text_color'    => '',
		'dark_link_color'    => '',
		'font_family_body'   => 'system',
		'font_family_heading' => 'system',
		'font_size_base'     => 16,
		'line_height_base'   => 1.6,
		'font_weight_heading' => '',
		'heading_text_transform' => 'none',
		'heading_letter_spacing' => 0,
		'container_width'    => 1200,
		'content_width'      => 800,
		'site_layout'        => 'full',
		'site_boxed_bg'      => '#f1f5f9',
		'btn_bg'             => '',
		'btn_text'           => '#ffffff',
		'btn_bg_hover'       => '',
		'btn_text_hover'     => '',
		'btn_radius'         => 4,
		'btn_padding_v'      => 12,
		'btn_padding_h'      => 20,
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
		'header_shadow'      => false,
		'header_transparent' => false,
		'menu_alignment'     => 'right',
		'menu_hover_style'   => 'color',
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
		'footer_align'       => 'center',
		'footer_copyright'   => '',
		'blog_thumbnails'    => true,
		'blog_post_meta'     => true,
		'blog_excerpt_length' => 30,
		'shop_columns'       => 4,
		'shop_per_page'      => 12,
		'header_cart'        => true,
		'arc_tpl_palette'    => 'default',
		'arc_tpl_color_brand' => '#59B8A9',
		'arc_tpl_color_brand_dark' => '#3E8E80',
		'arc_tpl_color_accent' => '#D6F73A',
		'arc_tpl_color_navy' => '#0F1C2E',
		'arc_tpl_color_mint' => '#F5F8F6',
		'arc_tpl_color_cream' => '#EAF1E8',
		'arc_tpl_color_cta_bg' => '#D6F73A',
		'arc_tpl_color_cta_text' => '#0F1C2E',
		'arc_tpl_link_primary' => 'default',
		'arc_tpl_link_primary_url' => '',
		'arc_tpl_link_partner' => 'default',
		'arc_tpl_link_partner_url' => '',
		'arc_tpl_link_secondary' => 'default',
		'arc_tpl_link_secondary_url' => '',
		'arc_tpl_copy_md'    => '',
	];
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
