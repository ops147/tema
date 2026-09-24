<?php
/**
 * The template for displaying header.
 *
 * @package Lienzo
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$site_name = get_bloginfo( 'name' );
$tagline   = get_bloginfo( 'description', 'display' );
$menu_args = [
	'theme_location' => 'menu-1',
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
];
$header_nav_menu = wp_nav_menu( $menu_args );
$header_mobile_nav_menu = wp_nav_menu( $menu_args ); // The same menu but separate call to avoid duplicate ID attributes.

$header_classes = [ 'site-header' ];

// Reuses the dynamic header's breakpoint classes so the horizontal menu
// collapses into the dropdown toggle below the chosen viewport width.
$menu_dropdown = apply_filters( 'lienzo_static_header_menu_dropdown', 'menu-dropdown-tablet' );
if ( $menu_dropdown ) {
	$header_classes[] = $menu_dropdown;
}

if ( function_exists( 'lienzoastra_get_option' ) && 'center' === lienzoastra_get_option( 'header_layout' ) ) {
	$header_classes[] = 'header-layout-center';
}

if ( function_exists( 'lienzoastra_get_option' ) && lienzoastra_get_option( 'header_full_width' ) ) {
	$header_classes[] = 'header-full-width';
}
?>

<header id="site-header" class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>">

	<div class="site-branding">
		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		} elseif ( $site_name ) {
			?>
			<div class="site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr__( 'Home', 'lienzo-astra' ); ?>" rel="home">
					<?php echo esc_html( $site_name ); ?>
				</a>
			</div>
			<?php if ( $tagline ) : ?>
			<p class="site-description">
				<?php echo esc_html( $tagline ); ?>
			</p>
			<?php endif; ?>
		<?php } ?>
	</div>

	<?php if ( $header_nav_menu ) : ?>
		<nav class="site-navigation" aria-label="<?php echo esc_attr__( 'Main menu', 'lienzo-astra' ); ?>">
			<?php
			// PHPCS - escaped by WordPress with "wp_nav_menu"
			echo $header_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</nav>

		<div class="site-navigation-toggle-holder">
			<button type="button" class="site-navigation-toggle" aria-expanded="false" aria-label="<?php echo esc_attr__( 'Menu', 'lienzo-astra' ); ?>">
				<span class="site-navigation-toggle-icon" aria-hidden="true"></span>
			</button>
		</div>
		<nav class="site-navigation-dropdown" aria-label="<?php echo esc_attr__( 'Mobile menu', 'lienzo-astra' ); ?>" aria-hidden="true" inert>
			<?php
			// PHPCS - escaped by WordPress with "wp_nav_menu"
			echo $header_mobile_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</nav>
	<?php endif; ?>

	<?php
	if ( function_exists( 'lienzoastra_cart_link' ) ) {
		echo lienzoastra_cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</header>
