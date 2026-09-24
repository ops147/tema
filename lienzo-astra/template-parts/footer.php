<?php
/**
 * The template for displaying footer.
 *
 * @package Lienzo
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$footer_nav_menu = wp_nav_menu( [
	'theme_location' => 'menu-2',
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
] );

$footer_classes = [ 'site-footer' ];
$footer_copyright = '';

if ( function_exists( 'lienzoastra_get_option' ) ) {
	if ( lienzoastra_get_option( 'footer_full_width' ) ) {
		$footer_classes[] = 'footer-full-width';
	}
	$footer_copyright = trim( (string) lienzoastra_get_option( 'footer_copyright' ) );
	if ( '' !== $footer_copyright ) {
		$footer_copyright = str_replace(
			[ '[year]', '[site]' ],
			[ wp_date( 'Y' ), get_bloginfo( 'name' ) ],
			$footer_copyright
		);
	}
}
?>
<footer id="site-footer" class="<?php echo esc_attr( implode( ' ', $footer_classes ) ); ?>">
	<?php if ( $footer_nav_menu ) : ?>
		<nav class="site-navigation" aria-label="<?php echo esc_attr__( 'Footer menu', 'lienzo-astra' ); ?>">
			<?php
			// PHPCS - escaped by WordPress with "wp_nav_menu"
			echo $footer_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</nav>
	<?php endif; ?>

	<?php if ( '' !== $footer_copyright ) : ?>
		<div class="copyright">
			<p><?php echo esc_html( $footer_copyright ); ?></p>
		</div>
	<?php endif; ?>
</footer>
