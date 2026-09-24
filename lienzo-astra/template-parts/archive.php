<?php
/**
 * The template for displaying archive pages.
 *
 * @package Lienzo
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<main id="content" class="site-main">

	<?php echo lienzo_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if ( apply_filters( 'lienzo_page_title', true ) ) : ?>
		<div class="page-header">
			<?php
			the_archive_title( '<h1 class="entry-title">', '</h1>' );
			the_archive_description( '<p class="archive-description">', '</p>' );
			?>
		</div>
	<?php endif; ?>

	<div class="page-content">
		<?php
		while ( have_posts() ) {
			the_post();
			$post_link = get_permalink();
			?>
			<article class="post">
				<?php
				printf( '<h2 class="%s"><a href="%s">%s</a></h2>', 'entry-title', esc_url( $post_link ), wp_kses_post( get_the_title() ) );
				if ( has_post_thumbnail() && ( ! function_exists( 'lienzoastra_blog_show_thumbnail' ) || lienzoastra_blog_show_thumbnail() ) ) {
					printf( '<a href="%s">%s</a>', esc_url( $post_link ), get_the_post_thumbnail( $post, 'large' ) );
				}
				the_excerpt();
				?>
			</article>
		<?php } ?>
	</div>

	<?php
	global $wp_query;
	if ( $wp_query->max_num_pages > 1 ) :
		$prev_arrow = is_rtl() ? '&rarr;' : '&larr;';
		$next_arrow = is_rtl() ? '&larr;' : '&rarr;';
		?>
		<nav class="pagination">
			<div class="nav-previous"><?php
				/* translators: %s: HTML entity for arrow character. */
				previous_posts_link( sprintf( esc_html__( '%s Previous', 'lienzo-astra' ), sprintf( '<span class="meta-nav">%s</span>', $prev_arrow ) ) );
			?></div>
			<div class="nav-next"><?php
				/* translators: %s: HTML entity for arrow character. */
				next_posts_link( sprintf( esc_html__( 'Next %s', 'lienzo-astra' ), sprintf( '<span class="meta-nav">%s</span>', $next_arrow ) ) );
			?></div>
		</nav>
	<?php endif; ?>

</main>
