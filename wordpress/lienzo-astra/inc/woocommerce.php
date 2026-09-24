<?php
/**
 * WooCommerce extras — shop grid controls and a header cart icon.
 *
 * In the spirit of Astra's and OceanWP's store options, this module adds a
 * "WooCommerce" section to Design Options (products per row, products per
 * page, header cart) and ships a small stylesheet for the cart badge.
 * Everything backs off completely when WooCommerce is not active.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzoastra_woocommerce_active' ) ) {
	/**
	 * Whether WooCommerce is loaded.
	 *
	 * @return bool
	 */
	function lienzoastra_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}

if ( ! function_exists( 'lienzoastra_woocommerce_extras_enabled' ) ) {
	/**
	 * Whether the WooCommerce extras (grid controls, header cart) are on.
	 *
	 * @return bool
	 */
	function lienzoastra_woocommerce_extras_enabled() {
		return lienzoastra_woocommerce_active() && (bool) apply_filters( 'lienzoastra_woocommerce_extras', true );
	}
}

if ( ! function_exists( 'lienzoastra_woocommerce_customize_register' ) ) {
	/**
	 * Add the "WooCommerce" section to the Design Options panel.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 *
	 * @return void
	 */
	function lienzoastra_woocommerce_customize_register( $wp_customize ) {
		if ( ! lienzoastra_woocommerce_extras_enabled() ) {
			return;
		}

		$wp_customize->add_section(
			'lienzoastra_woocommerce',
			[
				'title' => __( 'WooCommerce', 'lienzo-astra' ),
				'panel' => 'lienzoastra_design_options',
			]
		);

		$wp_customize->add_setting(
			'lienzoastra_shop_columns',
			[
				'default'           => '',
				'sanitize_callback' => 'absint',
			]
		);
		$wp_customize->add_control(
			'lienzoastra_shop_columns',
			[
				'label'       => __( 'Products per row', 'lienzo-astra' ),
				'section'     => 'lienzoastra_woocommerce',
				'type'        => 'range',
				'input_attrs' => [
					'min'  => 1,
					'max'  => 6,
					'step' => 1,
				],
			]
		);

		$wp_customize->add_setting(
			'lienzoastra_shop_per_page',
			[
				'default'           => '',
				'sanitize_callback' => 'absint',
			]
		);
		$wp_customize->add_control(
			'lienzoastra_shop_per_page',
			[
				'label'       => __( 'Products per page', 'lienzo-astra' ),
				'section'     => 'lienzoastra_woocommerce',
				'type'        => 'range',
				'input_attrs' => [
					'min'  => 4,
					'max'  => 48,
					'step' => 4,
				],
			]
		);

		$wp_customize->add_setting(
			'lienzoastra_header_cart',
			[
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			]
		);
		$wp_customize->add_control(
			'lienzoastra_header_cart',
			[
				'label'   => __( 'Show cart icon in the header', 'lienzo-astra' ),
				'section' => 'lienzoastra_woocommerce',
				'type'    => 'checkbox',
			]
		);
	}
}
add_action( 'customize_register', 'lienzoastra_woocommerce_customize_register' );

if ( ! function_exists( 'lienzoastra_loop_shop_columns' ) ) {
	/**
	 * Apply the "Products per row" option. When the option has never been
	 * saved the WooCommerce default is left untouched.
	 *
	 * @param int $columns Default column count.
	 *
	 * @return int
	 */
	function lienzoastra_loop_shop_columns( $columns ) {
		$saved = get_theme_mod( 'lienzoastra_shop_columns' );

		return ( '' === $saved || false === $saved ) ? $columns : absint( $saved );
	}
}

if ( ! function_exists( 'lienzoastra_loop_shop_per_page' ) ) {
	/**
	 * Apply the "Products per page" option. When the option has never been
	 * saved the WooCommerce default is left untouched.
	 *
	 * @param int $per_page Default products per page.
	 *
	 * @return int
	 */
	function lienzoastra_loop_shop_per_page( $per_page ) {
		$saved = get_theme_mod( 'lienzoastra_shop_per_page' );

		return ( '' === $saved || false === $saved ) ? $per_page : absint( $saved );
	}
}

if ( ! function_exists( 'lienzoastra_woocommerce_hooks' ) ) {
	/**
	 * Wire the shop grid filters once WooCommerce is around.
	 *
	 * @return void
	 */
	function lienzoastra_woocommerce_hooks() {
		if ( ! lienzoastra_woocommerce_extras_enabled() ) {
			return;
		}

		add_filter( 'loop_shop_columns', 'lienzoastra_loop_shop_columns' );
		add_filter( 'loop_shop_per_page', 'lienzoastra_loop_shop_per_page' );
	}
}
add_action( 'init', 'lienzoastra_woocommerce_hooks' );

if ( ! function_exists( 'lienzoastra_cart_link' ) ) {
	/**
	 * Header cart icon with a live item-count badge.
	 *
	 * Shared by the static and dynamic headers; returns an empty string
	 * when the cart icon is disabled or WooCommerce is inactive.
	 *
	 * @return string
	 */
	function lienzoastra_cart_link() {
		if ( ! lienzoastra_woocommerce_extras_enabled() || ! lienzoastra_get_option( 'header_cart' ) ) {
			return '';
		}

		$count = ( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

		ob_start();
		?>
		<a class="lienzoastra-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
			<span class="lienzoastra-cart-count"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
			<span class="screen-reader-text">
				<?php
				printf(
					/* translators: %s: number of items in the cart. */
					esc_html( _n( '%s item in the cart', '%s items in the cart', $count, 'lienzo-astra' ) ),
					esc_html( number_format_i18n( $count ) )
				);
				?>
			</span>
		</a>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lienzoastra_cart_fragments' ) ) {
	/**
	 * Keep the header cart count in sync after ajax add-to-cart events.
	 *
	 * @param array $fragments Selectors and replacement HTML.
	 *
	 * @return array
	 */
	function lienzoastra_cart_fragments( $fragments ) {
		$fragments['a.lienzoastra-cart-link'] = lienzoastra_cart_link();

		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'lienzoastra_cart_fragments' );

if ( ! function_exists( 'lienzoastra_woocommerce_styles' ) ) {
	/**
	 * Enqueue the small WooCommerce stylesheet (cart badge, shop polish).
	 *
	 * @return void
	 */
	function lienzoastra_woocommerce_styles() {
		if ( lienzo_is_arc_portal_page() || ! lienzoastra_woocommerce_extras_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'lienzoastra-woocommerce',
			LIENZOASTRA_URI . '/assets/css/woocommerce.css',
			[],
			lienzo_asset_version( 'assets/css/woocommerce.css' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lienzoastra_woocommerce_styles' );
