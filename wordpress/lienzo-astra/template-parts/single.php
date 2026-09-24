<?php
/**
 * The template for displaying singular post-types: posts, pages and user-defined custom post types.
 *
 * @package Lienzo
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

while ( have_posts() ) :
	the_post();

	// ARC starter templates are complete page designs — the theme keeps the
	// site chrome but steps aside inside <main>: no boxed content wrapper,
	// page title, breadcrumbs, tags, post navigation or comment form.
	$is_arc_template = function_exists( 'lienzo_is_arc_template_page' ) && lienzo_is_arc_template_page();
	?>

<main id="content" <?php post_class( 'site-main' ); ?>>

	<?php if ( ! $is_arc_template ) : ?>

		<?php echo lienzo_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if ( apply_filters( 'lienzo_page_title', true ) ) : ?>
			<div class="page-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<?php if ( 'post' === get_post_type() ) : ?>
					<?php echo function_exists( 'lienzoastra_post_meta' ) ? lienzoastra_post_meta() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lienzo-reading-time">
						<svg aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
						<?php echo lienzo_reading_time_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php endif; ?>

	<div class="page-content">
		<?php the_content(); ?>

		<?php wp_link_pages(); ?>

		<?php if ( ! $is_arc_template && has_tag() ) : ?>
		<div class="post-tags">
			<?php the_tags( '<span class="tag-links">' . esc_html__( 'Tagged ', 'lienzo-astra' ), ', ', '</span>' ); ?>
		</div>
		<?php endif; ?>
	</div>

	<?php
	if ( ! $is_arc_template && 'post' === get_post_type() ) {
		the_post_navigation(
			[
				'prev_text' => '<span class="meta-nav">&larr;</span> %title',
				'next_text' => '%title <span class="meta-nav">&rarr;</span>',
			]
		);
	}
	?>

	<?php
	if ( ! $is_arc_template ) {
		comments_template();
	}
	?>

</main>

	<?php
endwhile;
