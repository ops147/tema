<?php
/**
 * Appearance > Lienzo: a plain WordPress Settings API page with switches
 * that turn off individual theme features.
 *
 * All values live in a single option (LIENZOASTRA_OPTION) as `key => 0|1`.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Setting key => filter that gets forced to `false` when the switch is on.
 * Kept free of translated strings so it is safe to read on `init`.
 *
 * @return array<string,string>
 */
function lienzo_get_settings_filters() {
	return [
		'disable_description_meta'   => 'lienzo_description_meta_tag',
		'disable_skip_link'          => 'lienzo_enable_skip_link',
		'disable_header_footer'      => 'lienzo_header_footer',
		'disable_page_title'         => 'lienzo_page_title',
		'disable_reset_style'        => 'lienzo_enqueue_reset',
		'disable_theme_style'        => 'lienzo_enqueue_theme_style',
		'disable_seo_tags'           => 'lienzo_seo_meta_tags',
		'disable_performance_tweaks' => 'lienzo_performance_tweaks',
		'disable_security_hardening' => 'lienzo_security_hardening',
		'disable_dark_mode'          => 'lienzo_enable_dark_mode',
		'disable_back_to_top'        => 'lienzo_enable_back_to_top',
		'disable_reading_progress'   => 'lienzo_enable_reading_progress',
		'disable_breadcrumbs'        => 'lienzo_enable_breadcrumbs',
		'disable_woocommerce_extras' => 'lienzoastra_woocommerce_extras',
	];
}

/**
 * Labels and help texts shown on the settings screen.
 *
 * @return array<string,array{label:string,help:string}>
 */
function lienzo_get_settings_labels() {
	return [
		'disable_description_meta' => [
			'label' => __( 'Description meta tag', 'lienzo-astra' ),
			'help'  => __( 'Disable the meta description tag generated from the page excerpt.', 'lienzo-astra' ),
		],
		'disable_skip_link'        => [
			'label' => __( 'Skip link', 'lienzo-astra' ),
			'help'  => __( 'Disable the "Skip to content" accessibility link.', 'lienzo-astra' ),
		],
		'disable_header_footer'    => [
			'label' => __( 'Theme header & footer', 'lienzo-astra' ),
			'help'  => __( 'Disable the theme header and footer completely (use this when Elementor or a plugin provides them).', 'lienzo-astra' ),
		],
		'disable_page_title'       => [
			'label' => __( 'Page title', 'lienzo-astra' ),
			'help'  => __( 'Disable the page title the theme prints above the content.', 'lienzo-astra' ),
		],
		'disable_reset_style'      => [
			'label' => __( 'Reset stylesheet', 'lienzo-astra' ),
			'help'  => __( 'Do not load reset.css.', 'lienzo-astra' ),
		],
		'disable_theme_style'      => [
			'label' => __( 'Theme stylesheet', 'lienzo-astra' ),
			'help'  => __( 'Do not load theme.css.', 'lienzo-astra' ),
		],
		'disable_seo_tags'           => [
			'label' => __( 'SEO meta tags', 'lienzo-astra' ),
			'help'  => __( 'Disable the built-in Open Graph, Twitter Card and JSON-LD tags (always off automatically when Yoast, Rank Math, SEOPress, AIOSEO or The SEO Framework is active).', 'lienzo-astra' ),
		],
		'disable_performance_tweaks' => [
			'label' => __( 'Performance tweaks', 'lienzo-astra' ),
			'help'  => __( 'Disable removal of emoji scripts, RSD/wlwmanifest/shortlink links and the dashicons front-end stylesheet.', 'lienzo-astra' ),
		],
		'disable_security_hardening' => [
			'label' => __( 'Security hardening', 'lienzo-astra' ),
			'help'  => __( 'Disable pingback removal, the generic login error message and REST API user-list restriction.', 'lienzo-astra' ),
		],
		'disable_dark_mode'          => [
			'label' => __( 'Dark mode', 'lienzo-astra' ),
			'help'  => __( 'Remove the dark/light mode engine and its toggle button.', 'lienzo-astra' ),
		],
		'disable_back_to_top'        => [
			'label' => __( 'Back-to-top button', 'lienzo-astra' ),
			'help'  => __( 'Remove the floating back-to-top button.', 'lienzo-astra' ),
		],
		'disable_reading_progress'   => [
			'label' => __( 'Reading progress bar', 'lienzo-astra' ),
			'help'  => __( 'Remove the scroll progress bar on single posts.', 'lienzo-astra' ),
		],
		'disable_breadcrumbs'        => [
			'label' => __( 'Breadcrumbs', 'lienzo-astra' ),
			'help'  => __( 'Disable the breadcrumb trail (fallback templates, shortcode and Elementor widget).', 'lienzo-astra' ),
		],
		'disable_woocommerce_extras' => [
			'label' => __( 'WooCommerce extras', 'lienzo-astra' ),
			'help'  => __( 'Disable the WooCommerce shop controls (products per row/page, header cart). Only applies when WooCommerce is active.', 'lienzo-astra' ),
		],
	];
}

/**
 * Apply the saved switches by forcing the matching filters to `false`.
 *
 * @return void
 */
function lienzo_apply_settings() {
	$saved = get_option( LIENZOASTRA_OPTION, [] );

	if ( ! is_array( $saved ) ) {
		return;
	}

	foreach ( lienzo_get_settings_filters() as $key => $filter ) {
		if ( ! empty( $saved[ $key ] ) ) {
			add_filter( $filter, '__return_false' );
		}
	}
}
add_action( 'init', 'lienzo_apply_settings', 0 );

/**
 * Register the option.
 *
 * @return void
 */
function lienzo_register_settings() {
	register_setting(
		'lienzo_settings',
		LIENZOASTRA_OPTION,
		[
			'type'              => 'array',
			'default'           => [],
			'sanitize_callback' => 'lienzo_sanitize_settings',
		]
	);
}
add_action( 'admin_init', 'lienzo_register_settings' );

/**
 * Keep only known keys and cast them to 0|1.
 *
 * @param mixed $input Raw submitted value.
 *
 * @return array<string,int>
 */
function lienzo_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : [];
	$clean = [];

	foreach ( array_keys( lienzo_get_settings_filters() ) as $key ) {
		$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
	}

	return $clean;
}

/**
 * Add the screen under Appearance.
 *
 * @return void
 */
function lienzo_add_settings_page() {
	add_theme_page(
		__( 'Lienzo Astra settings', 'lienzo-astra' ),
		__( 'Lienzo Astra', 'lienzo-astra' ),
		'manage_options',
		'lienzo-settings',
		'lienzo_render_settings_page'
	);
}
add_action( 'admin_menu', 'lienzo_add_settings_page' );

/**
 * Render the screen.
 *
 * @return void
 */
function lienzo_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$saved = get_option( LIENZOASTRA_OPTION, [] );
	$saved = is_array( $saved ) ? $saved : [];
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Turn off theme features you would rather build yourself with Elementor.', 'lienzo-astra' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'lienzo_settings' ); ?>

			<table class="form-table" role="presentation">
				<?php foreach ( lienzo_get_settings_labels() as $key => $field ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $field['label'] ); ?></th>
						<td>
							<label for="lienzo-<?php echo esc_attr( $key ); ?>">
								<input
									type="checkbox"
									id="lienzo-<?php echo esc_attr( $key ); ?>"
									name="<?php echo esc_attr( LIENZOASTRA_OPTION . '[' . $key . ']' ); ?>"
									value="1"
									<?php checked( ! empty( $saved[ $key ] ) ); ?>
								>
								<?php echo esc_html( $field['help'] ); ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button(); ?>
		</form>

		<?php
		/**
		 * Extra sections rendered below the settings form (starter sites…).
		 */
		do_action( 'lienzoastra_settings_after_form' );
		?>
	</div>
	<?php
}
