<?php
/**
 * The template for displaying all WooCommerce pages.
 *
 * Wraps shop, product, cart and checkout views in the theme's own markup
 * (breadcrumbs + content column) instead of letting WooCommerce borrow a
 * generic page template.
 *
 * @package LienzoAstra
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>
<main id="content" class="site-main">

	<?php echo function_exists( 'lienzo_breadcrumbs' ) ? lienzo_breadcrumbs() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div class="page-content">
		<?php woocommerce_content(); ?>
	</div>

</main>
<?php
get_footer();
