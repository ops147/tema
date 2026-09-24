<?php
/**
 * Title: Testimonials — three columns
 * Slug: lienzo-astra/testimonials
 * Description: Three customer testimonials on a soft surface background.
 * Categories: lienzo-astra, columns, testimonials
 * Keywords: testimonials, reviews, quotes
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'What our customers say', 'lienzo-astra' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( '“The whole experience was smooth from day one. It saved us hours every single week.”', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size"><strong><?php esc_html_e( 'Alex Rivera', 'lienzo-astra' ); ?></strong> — <?php esc_html_e( 'Product Manager', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( '“Setup took minutes, not days. The team finally has a tool everyone actually uses.”', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size"><strong><?php esc_html_e( 'Sam Chen', 'lienzo-astra' ); ?></strong> — <?php esc_html_e( 'Founder', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( '“Reliable, fast and easy to customize. Exactly what we were looking for.”', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"small"} -->
			<p class="has-small-font-size"><strong><?php esc_html_e( 'Jordan Lee', 'lienzo-astra' ); ?></strong> — <?php esc_html_e( 'Operations Lead', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
