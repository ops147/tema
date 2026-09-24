<?php
/**
 * Title: Stats — band
 * Slug: lienzo-astra/stats-band
 * Description: Highlighted numbers band (customers, years, countries) in brand color.
 * Categories: lienzo-astra, columns, featured
 * Keywords: stats, numbers, counter
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"primary","textColor":"light","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-light-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-x-large-font-size">10k+</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
			<p class="has-text-align-center has-small-font-size"><?php esc_html_e( 'Happy customers', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-x-large-font-size">99.9%</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
			<p class="has-text-align-center has-small-font-size"><?php esc_html_e( 'Uptime last year', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"x-large"} -->
			<h3 class="wp-block-heading has-text-align-center has-x-large-font-size">24/7</h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
			<p class="has-text-align-center has-small-font-size"><?php esc_html_e( 'Human support', 'lienzo-astra' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
