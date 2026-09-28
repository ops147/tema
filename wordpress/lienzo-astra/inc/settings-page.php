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
 * Visual grouping of the feature switches on the settings screen.
 *
 * @return array<string,array{label:string,icon:string,keys:string[]}>
 */
function lienzo_get_settings_groups() {
	return [
		'layout'     => [
			'label' => __( 'Layout & structure', 'lienzo-astra' ),
			'icon'  => 'dashicons-layout',
			'keys'  => [ 'disable_header_footer', 'disable_page_title', 'disable_breadcrumbs' ],
		],
		'appearance' => [
			'label' => __( 'Appearance', 'lienzo-astra' ),
			'icon'  => 'dashicons-admin-appearance',
			'keys'  => [ 'disable_reset_style', 'disable_theme_style', 'disable_dark_mode' ],
		],
		'ux'         => [
			'label' => __( 'UX extras', 'lienzo-astra' ),
			'icon'  => 'dashicons-star-filled',
			'keys'  => [ 'disable_skip_link', 'disable_back_to_top', 'disable_reading_progress' ],
		],
		'seo'        => [
			'label' => __( 'SEO & metadata', 'lienzo-astra' ),
			'icon'  => 'dashicons-chart-line',
			'keys'  => [ 'disable_description_meta', 'disable_seo_tags' ],
		],
		'perfsec'    => [
			'label' => __( 'Performance & security', 'lienzo-astra' ),
			'icon'  => 'dashicons-shield',
			'keys'  => [ 'disable_performance_tweaks', 'disable_security_hardening' ],
		],
		'woo'        => [
			'label' => __( 'WooCommerce', 'lienzo-astra' ),
			'icon'  => 'dashicons-cart',
			'keys'  => [ 'disable_woocommerce_extras' ],
		],
	];
}

/**
 * Integration chips: companion plugins the theme cooperates with.
 *
 * @return array<int,array{name:string,active:bool,meta:string,url:string}>
 */
function lienzo_settings_integrations() {
	$items = [];

	$arc_on   = function_exists( 'lienzoastra_arc_st_active' ) && lienzoastra_arc_st_active();
	$arc_cnt  = $arc_on && function_exists( 'lienzoastra_arc_st_pages' ) ? count( (array) lienzoastra_arc_st_pages() ) : 0;
	$items[]  = [
		'name'   => 'ARC Starter Templates',
		'active' => $arc_on,
		'meta'   => $arc_on
			/* translators: %d: number of imported template pages. */
			? sprintf( __( '%d pages imported', 'lienzo-astra' ), $arc_cnt )
			: __( 'Not active', 'lienzo-astra' ),
		'url'    => $arc_on ? admin_url( 'admin.php?page=arc-starter-templates' ) : '',
	];

	$careers_on = function_exists( 'arc_careers_option' );
	$items[]    = [
		'name'   => 'ARC Careers',
		'active' => $careers_on,
		'meta'   => $careers_on ? __( 'Portals & application forms', 'lienzo-astra' ) : __( 'Not active', 'lienzo-astra' ),
		'url'    => $careers_on ? admin_url( 'admin.php?page=arc-careers' ) : '',
	];

	$page_on    = function_exists( 'lienzoastra_page_clones_active' ) && lienzoastra_page_clones_active();
	$items[]    = [
		'name'   => 'PAge cloned sites',
		'active' => $page_on,
		'meta'   => $page_on ? __( 'Starter provider active', 'lienzo-astra' ) : __( 'Not active', 'lienzo-astra' ),
		'url'    => $page_on ? admin_url( 'admin.php?page=page-clones' ) : '',
	];

	$items[] = [
		'name'   => 'Elementor',
		'active' => did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ),
		'meta'   => __( 'Page builder', 'lienzo-astra' ),
		'url'    => '',
	];

	$items[] = [
		'name'   => 'WooCommerce',
		'active' => class_exists( 'WooCommerce' ),
		'meta'   => __( 'Shop integration', 'lienzo-astra' ),
		'url'    => '',
	];

	return $items;
}

/**
 * Styles + script for the settings screen, only on its own hook.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function lienzo_settings_assets( $hook ) {
	if ( 'appearance_page_lienzo-settings' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'lienzoastra-settings', LIENZOASTRA_URI . '/assets/css/admin-settings.css', [], lienzo_asset_version( 'assets/css/admin-settings.css' ) );
	wp_enqueue_script( 'lienzoastra-settings', LIENZOASTRA_URI . '/assets/js/admin-settings.js', [], lienzo_asset_version( 'assets/js/admin-settings.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'lienzo_settings_assets' );

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

	$saved  = get_option( LIENZOASTRA_OPTION, [] );
	$saved  = is_array( $saved ) ? $saved : [];
	$labels = lienzo_get_settings_labels();
	?>
	<div class="wrap la-settings">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<div class="la-hero">
			<div class="la-hero__brand">
				<div class="la-hero__title">
					<?php esc_html_e( 'Lienzo Astra', 'lienzo-astra' ); ?>
					<span class="la-hero__version">v<?php echo esc_html( LIENZOASTRA_VERSION ); ?></span>
				</div>
				<p class="la-hero__desc">
					<?php esc_html_e( 'Lightweight base tuned for the ARC starter sites: the plugin imports the pages, this theme is where they are customized. Turn off any feature you would rather build yourself with Elementor.', 'lienzo-astra' ); ?>
				</p>
			</div>
			<div class="la-hero__actions">
				<a class="button la-btn-lime" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
					<?php esc_html_e( 'Open Customizer', 'lienzo-astra' ); ?>
				</a>
				<a class="button la-btn-ghost" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=lienzoastra_arc_template' ) ); ?>">
					<?php esc_html_e( 'ARC Template options', 'lienzo-astra' ); ?>
				</a>
				<?php if ( function_exists( 'lienzoastra_arc_st_active' ) && lienzoastra_arc_st_active() ) : ?>
					<a class="button la-btn-ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=arc-starter-templates' ) ); ?>">
						<?php esc_html_e( 'Import templates', 'lienzo-astra' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="la-integrations">
			<?php foreach ( lienzo_settings_integrations() as $chip ) : ?>
				<?php $tag = '' !== $chip['url'] ? 'a' : 'div'; ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					class="la-chip <?php echo $chip['active'] ? 'is-active' : ''; ?>"
					<?php echo '' !== $chip['url'] ? 'href="' . esc_url( $chip['url'] ) . '"' : ''; ?>>
					<span class="la-chip__dot" aria-hidden="true"></span>
					<span>
						<span class="la-chip__name"><?php echo esc_html( $chip['name'] ); ?></span>
						<span class="la-chip__meta"><?php echo esc_html( $chip['meta'] ); ?></span>
					</span>
					<?php if ( '' !== $chip['url'] ) : ?>
						<span class="la-chip__go dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					<?php endif; ?>
				</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endforeach; ?>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'lienzo_settings' ); ?>

			<div class="la-toolbar">
				<input type="search" id="la-filter" class="la-toolbar__search" placeholder="<?php esc_attr_e( 'Filter features…', 'lienzo-astra' ); ?>" />
				<div class="la-toolbar__bulk">
					<button type="button" class="button" id="la-enable-all"><?php esc_html_e( 'Enable all', 'lienzo-astra' ); ?></button>
					<button type="button" class="button" id="la-disable-all"><?php esc_html_e( 'Disable all', 'lienzo-astra' ); ?></button>
				</div>
			</div>

			<?php foreach ( lienzo_get_settings_groups() as $group ) : ?>
				<section class="la-card la-group">
					<header class="la-card__head">
						<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>" aria-hidden="true"></span>
						<h2 class="la-card__title"><?php echo esc_html( $group['label'] ); ?></h2>
					</header>
					<div class="la-card__body">
						<?php foreach ( $group['keys'] as $key ) : ?>
							<?php if ( ! isset( $labels[ $key ] ) ) { continue; } ?>
							<?php $field = $labels[ $key ]; $disabled = ! empty( $saved[ $key ] ); ?>
							<div class="la-row" data-search="<?php echo esc_attr( $field['label'] . ' ' . $field['help'] ); ?>">
								<div class="la-row__text">
									<label class="la-row__label" for="lienzo-<?php echo esc_attr( $key ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
									</label>
									<span class="la-row__help"><?php echo esc_html( $field['help'] ); ?></span>
								</div>
								<span class="la-row__state <?php echo $disabled ? 'is-off' : 'is-on'; ?>"
									data-on="<?php esc_attr_e( 'Enabled', 'lienzo-astra' ); ?>"
									data-off="<?php esc_attr_e( 'Disabled', 'lienzo-astra' ); ?>">
									<?php echo $disabled ? esc_html__( 'Disabled', 'lienzo-astra' ) : esc_html__( 'Enabled', 'lienzo-astra' ); ?>
								</span>
								<span class="la-switch" title="<?php esc_attr_e( 'Toggle to disable this feature', 'lienzo-astra' ); ?>">
									<input
										type="checkbox"
										id="lienzo-<?php echo esc_attr( $key ); ?>"
										name="<?php echo esc_attr( LIENZOASTRA_OPTION . '[' . $key . ']' ); ?>"
										value="1"
										<?php checked( $disabled ); ?>
									>
									<span class="la-switch__track" aria-hidden="true"></span>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>

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
