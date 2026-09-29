<?php
/**
 * Customizer — front-end CSS output (custom properties + chrome).
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
	$dark_bg          = lienzoastra_get_option( 'dark_bg_color' );
	$dark_text        = lienzoastra_get_option( 'dark_text_color' );
	$dark_link        = lienzoastra_get_option( 'dark_link_color' );
	$font_body        = lienzoastra_font_stack( lienzoastra_get_option( 'font_family_body' ) );
	$font_heading     = lienzoastra_font_stack( lienzoastra_get_option( 'font_family_heading' ) );
	$font_size        = (int) lienzoastra_get_option( 'font_size_base' );
	$line_height      = (float) lienzoastra_get_option( 'line_height_base' );
	$heading_weight   = lienzoastra_get_option( 'font_weight_heading' );
	$heading_transform = lienzoastra_get_option( 'heading_text_transform' );
	$heading_spacing  = (float) lienzoastra_get_option( 'heading_letter_spacing' );
	$container_width  = (int) lienzoastra_get_option( 'container_width' );
	$content_width    = (int) lienzoastra_get_option( 'content_width' );
	$site_layout      = lienzoastra_get_option( 'site_layout' );
	$boxed_bg         = lienzoastra_get_option( 'site_boxed_bg' );
	$btn_bg           = lienzoastra_get_option( 'btn_bg' );
	$btn_text         = lienzoastra_get_option( 'btn_text' );
	$btn_bg_hover     = lienzoastra_get_option( 'btn_bg_hover' );
	$btn_text_hover   = lienzoastra_get_option( 'btn_text_hover' );
	$btn_radius       = (int) lienzoastra_get_option( 'btn_radius' );
	$btn_padding_v    = (int) lienzoastra_get_option( 'btn_padding_v' );
	$btn_padding_h    = (int) lienzoastra_get_option( 'btn_padding_h' );
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
		--lienzoastra-heading-weight: <?php echo esc_html( '' !== $heading_weight ? $heading_weight : 'inherit' ); ?>;
		--lienzoastra-heading-transform: <?php echo esc_html( $heading_transform ); ?>;
		--lienzoastra-heading-letter-spacing: <?php echo esc_html( $heading_spacing ); ?>px;
		--lienzoastra-boxed-bg: <?php echo esc_html( '' !== $boxed_bg ? $boxed_bg : '#f1f5f9' ); ?>;
		--lienzoastra-btn-bg: <?php echo esc_html( '' !== $btn_bg ? $btn_bg : 'var(--lienzoastra-color-link)' ); ?>;
		--lienzoastra-btn-text: <?php echo esc_html( '' !== $btn_text ? $btn_text : '#ffffff' ); ?>;
		--lienzoastra-btn-bg-hover: <?php echo esc_html( '' !== $btn_bg_hover ? $btn_bg_hover : 'var(--lienzoastra-color-link-hover)' ); ?>;
		--lienzoastra-btn-text-hover: <?php echo esc_html( '' !== $btn_text_hover ? $btn_text_hover : 'var(--lienzoastra-btn-text)' ); ?>;
		--lienzoastra-btn-radius: <?php echo (int) $btn_radius; ?>px;
		--lienzoastra-btn-padding-v: <?php echo (int) $btn_padding_v; ?>px;
		--lienzoastra-btn-padding-h: <?php echo (int) $btn_padding_h; ?>px;
	}

	<?php
	// Optional dark-mode palette — overrides the variables the dark-mode
	// engine (html[data-lienzo-theme]) already consumes.
	if ( '' !== $dark_bg || '' !== $dark_text || '' !== $dark_link ) :
		?>
	html[data-lienzo-theme='dark'] {
		<?php if ( '' !== $dark_bg ) : ?>
		--lienzo-bg: <?php echo esc_html( $dark_bg ); ?>;
		<?php endif; ?>
		<?php if ( '' !== $dark_text ) : ?>
		--lienzo-text: <?php echo esc_html( $dark_text ); ?>;
		<?php endif; ?>
		<?php if ( '' !== $dark_link ) : ?>
		--lienzo-accent: <?php echo esc_html( $dark_link ); ?>;
		<?php endif; ?>
	}
	<?php endif; ?>

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
		font-weight: var(--lienzoastra-heading-weight);
		letter-spacing: var(--lienzoastra-heading-letter-spacing);
		text-transform: var(--lienzoastra-heading-transform);
	}

	<?php
	// Scoped to .site-main (plus block/Elementor buttons) so theme chrome
	// — nav toggles, dark-mode switch, scroll-to-top — keeps its own look.
	?>
	.site-main button,
	.site-main .button,
	.site-main input[type="button"],
	.site-main input[type="submit"],
	.site-main input[type="reset"],
	.wp-block-button__link,
	.elementor-button {
		background: var(--lienzoastra-btn-bg);
		border: 0;
		border-radius: var(--lienzoastra-btn-radius);
		color: var(--lienzoastra-btn-text);
		padding: var(--lienzoastra-btn-padding-v) var(--lienzoastra-btn-padding-h);
		transition: background .2s ease, color .2s ease;
	}

	.site-main button:hover,
	.site-main button:focus,
	.site-main .button:hover,
	.site-main .button:focus,
	.site-main input[type="button"]:hover,
	.site-main input[type="button"]:focus,
	.site-main input[type="submit"]:hover,
	.site-main input[type="submit"]:focus,
	.site-main input[type="reset"]:hover,
	.site-main input[type="reset"]:focus,
	.wp-block-button__link:hover,
	.wp-block-button__link:focus,
	.elementor-button:hover,
	.elementor-button:focus {
		background: var(--lienzoastra-btn-bg-hover);
		color: var(--lienzoastra-btn-text-hover);
	}

	<?php if ( 'boxed' === $site_layout ) : ?>
	body {
		background-color: var(--lienzoastra-boxed-bg);
	}

	body .site-main,
	body .page-header {
		background: var(--lienzoastra-color-background);
		box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
		padding-inline: 2rem;
	}
	<?php endif; ?>

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
	$footer_align      = lienzoastra_get_option( 'footer_align' );
	$header_shadow     = lienzoastra_get_option( 'header_shadow' );
	$header_transparent = lienzoastra_get_option( 'header_transparent' );
	$menu_hover        = lienzoastra_get_option( 'menu_hover_style' );
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
		--lienzoastra-footer-align: <?php echo esc_html( $footer_align ); ?>;
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

	<?php if ( $header_shadow ) : ?>
	.site-header {
		box-shadow: 0 1px 6px rgba(0, 0, 0, .1);
	}
	<?php endif; ?>

	<?php if ( $header_transparent ) : ?>
	body .site-header {
		background: transparent;
		border-bottom-color: transparent;
		left: 0;
		position: absolute;
		right: 0;
		top: 0;
	}
	body.admin-bar .site-header {
		top: 32px;
	}
	<?php endif; ?>

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

	<?php if ( 'underline' === $menu_hover ) : ?>
	.site-header .site-navigation ul.menu > li > a {
		position: relative;
	}
	.site-header .site-navigation ul.menu > li > a::after {
		background: var(--lienzoastra-menu-color-hover);
		bottom: -2px;
		content: "";
		height: 2px;
		left: 0;
		position: absolute;
		right: 0;
		transform: scaleX(0);
		transform-origin: left;
		transition: transform .25s ease;
	}
	.site-header .site-navigation ul.menu > li:hover > a::after,
	.site-header .site-navigation ul.menu > li.current-menu-item > a::after,
	.site-header .site-navigation ul.menu > li.current-menu-ancestor > a::after {
		transform: scaleX(1);
	}
	<?php endif; ?>

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
		text-align: var(--lienzoastra-footer-align);
	}
	.site-footer {
		text-align: var(--lienzoastra-footer-align);
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
