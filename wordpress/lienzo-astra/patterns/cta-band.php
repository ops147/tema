<?php
/**
 * Title: Call to action — band
 * Slug: lienzo-astra/cta-band
 * Description: Full-width dark call-to-action band with heading and button.
 * Categories: lienzo-astra, call-to-action, featured
 * Keywords: cta, banner, button
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"dark","textColor":"light","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-light-color has-dark-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Ready to get started?', 'lienzo-astra' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center"} -->
	<p class="has-text-align-center"><?php esc_html_e( 'One line that removes the last doubt and tells people exactly what happens next.', 'lienzo-astra' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"primary","textColor":"light"} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-light-color has-primary-background-color has-text-color has-background wp-element-button"><?php esc_html_e( 'Start today', 'lienzo-astra' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
