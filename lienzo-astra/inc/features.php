<?php
/**
 * Small, optional front-end features: dark mode, back-to-top, reading
 * progress bar, scroll reveal, estimated reading time and breadcrumbs.
 *
 * Every feature is opt-out (filter or Appearance > Lienzo switch) and the
 * markup is injected through `wp_body_open` / `wp_footer` so it works no
 * matter which header/footer (static, dynamic, or an Elementor Theme
 * Builder location) is active.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueue the extra stylesheet/script behind these features.
 */
if ( ! function_exists( 'lienzo_enqueue_feature_assets' ) ) {
	/**
	 * Enqueue assets when at least one feature is active.
	 *
	 * @return void
	 */
	function lienzo_enqueue_feature_assets() {
		if ( lienzo_is_arc_portal_page() || lienzo_is_arc_template_page() ) {
			return; // ARC pages bypass the theme — never restyle them.
		}

		$any_enabled = lienzo_dark_mode_enabled() || lienzo_back_to_top_enabled() || lienzo_reading_progress_enabled() || lienzo_scroll_reveal_enabled();

		if ( ! $any_enabled ) {
			return;
		}

		wp_enqueue_style( 'lienzo-enhancements', LIENZOASTRA_URI . '/assets/css/enhancements.css', [], lienzo_asset_version( 'assets/css/enhancements.css' ) );

		wp_enqueue_script( 'lienzo-enhancements', LIENZOASTRA_URI . '/assets/js/enhancements.js', [], lienzo_asset_version( 'assets/js/enhancements.js' ), true );

		wp_localize_script(
			'lienzo-enhancements',
			'lienzoFeatures',
			[
				'darkMode'        => lienzo_dark_mode_enabled(),
				'backToTop'       => lienzo_back_to_top_enabled(),
				'readingProgress' => lienzo_reading_progress_enabled() && is_singular( 'post' ),
				'scrollReveal'    => lienzo_scroll_reveal_enabled(),
				'i18n'            => [
					'toggleToDark'  => esc_html__( 'Switch to dark mode', 'lienzo-astra' ),
					'toggleToLight' => esc_html__( 'Switch to light mode', 'lienzo-astra' ),
					'backToTop'     => esc_html__( 'Back to top', 'lienzo-astra' ),
				],
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'lienzo_enqueue_feature_assets' );

/* -------------------------------------------------------------------------
 * Dark mode
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_dark_mode_enabled' ) ) {
	/**
	 * Whether the dark mode engine (CSS variables + toggle) is active.
	 *
	 * @return bool
	 */
	function lienzo_dark_mode_enabled() {
		return (bool) apply_filters( 'lienzo_enable_dark_mode', true );
	}
}

if ( ! function_exists( 'lienzo_dark_mode_toggle_button' ) ) {
	/**
	 * Markup for the dark/light mode toggle button.
	 *
	 * Shared by the automatic `wp_body_open` insertion and by the Elementor
	 * "Lienzo Dark Mode Switcher" widget, so both stay in sync.
	 *
	 * @return string
	 */
	function lienzo_dark_mode_toggle_button() {
		ob_start();
		?>
		<button type="button" class="lienzo-dark-toggle" aria-pressed="false" aria-label="<?php echo esc_attr__( 'Toggle dark mode', 'lienzo-astra' ); ?>">
			<span class="lienzo-dark-toggle-icon" aria-hidden="true">
				<svg class="icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
				<svg class="icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>
			</span>
		</button>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lienzo_output_dark_mode_toggle' ) ) {
	/**
	 * Print the auto-inserted toggle button right after <body>.
	 *
	 * @return void
	 */
	function lienzo_output_dark_mode_toggle() {
		if ( lienzo_is_arc_portal_page() || lienzo_is_arc_template_page() || ! lienzo_dark_mode_enabled() || ! apply_filters( 'lienzo_auto_insert_dark_mode_toggle', true ) ) {
			return;
		}

		echo lienzo_dark_mode_toggle_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_body_open', 'lienzo_output_dark_mode_toggle' );

/* -------------------------------------------------------------------------
 * Back to top
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_back_to_top_enabled' ) ) {
	/**
	 * Whether the back-to-top button is active.
	 *
	 * @return bool
	 */
	function lienzo_back_to_top_enabled() {
		return (bool) apply_filters( 'lienzo_enable_back_to_top', true );
	}
}

if ( ! function_exists( 'lienzo_output_back_to_top' ) ) {
	/**
	 * Print the back-to-top button before `wp_footer`.
	 *
	 * @return void
	 */
	function lienzo_output_back_to_top() {
		if ( lienzo_is_arc_portal_page() || lienzo_is_arc_template_page() || ! lienzo_back_to_top_enabled() ) {
			return;
		}
		?>
		<button type="button" class="lienzo-back-to-top" aria-label="<?php echo esc_attr__( 'Back to top', 'lienzo-astra' ); ?>">
			<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"></path></svg>
		</button>
		<?php
	}
}
add_action( 'wp_footer', 'lienzo_output_back_to_top' );

/* -------------------------------------------------------------------------
 * Reading progress bar
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_reading_progress_enabled' ) ) {
	/**
	 * Whether the reading progress bar is active.
	 *
	 * @return bool
	 */
	function lienzo_reading_progress_enabled() {
		return (bool) apply_filters( 'lienzo_enable_reading_progress', true );
	}
}

if ( ! function_exists( 'lienzo_output_reading_progress' ) ) {
	/**
	 * Print the (empty) reading progress bar element on single posts.
	 *
	 * @return void
	 */
	function lienzo_output_reading_progress() {
		if ( lienzo_is_arc_portal_page() || lienzo_is_arc_template_page() || ! lienzo_reading_progress_enabled() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<div class="lienzo-reading-progress" role="progressbar" aria-hidden="true">
			<div class="lienzo-reading-progress-bar"></div>
		</div>
		<?php
	}
}
add_action( 'wp_body_open', 'lienzo_output_reading_progress' );

/* -------------------------------------------------------------------------
 * Scroll reveal
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_scroll_reveal_enabled' ) ) {
	/**
	 * Whether the opt-in scroll-reveal animation (.lienzo-reveal /
	 * [data-lienzo-reveal]) is active. Purely additive — elements without
	 * the marker are never touched.
	 *
	 * @return bool
	 */
	function lienzo_scroll_reveal_enabled() {
		return (bool) apply_filters( 'lienzo_enable_scroll_reveal', true );
	}
}

/* -------------------------------------------------------------------------
 * Estimated reading time
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_reading_time' ) ) {
	/**
	 * Estimated reading time, in whole minutes (minimum 1).
	 *
	 * @param int|null $post_id Post ID, defaults to the current post.
	 *
	 * @return int
	 */
	function lienzo_reading_time( $post_id = null ) {
		$post_id = $post_id ? $post_id : get_the_ID();
		$content = (string) get_post_field( 'post_content', $post_id );
		$words   = preg_split( '/\s+/', trim( wp_strip_all_tags( $content ) ) );
		$count   = is_array( $words ) ? count( array_filter( $words ) ) : 0;
		$wpm     = max( 1, (int) apply_filters( 'lienzo_reading_time_wpm', 200 ) );

		return max( 1, (int) ceil( $count / $wpm ) );
	}
}

if ( ! function_exists( 'lienzo_reading_time_html' ) ) {
	/**
	 * Translated "X min read" string for the current (or given) post.
	 *
	 * @param int|null $post_id Post ID, defaults to the current post.
	 *
	 * @return string
	 */
	function lienzo_reading_time_html( $post_id = null ) {
		$minutes = lienzo_reading_time( $post_id );

		return sprintf(
			/* translators: %s: number of minutes. */
			esc_html( _n( '%s min read', '%s min read', $minutes, 'lienzo-astra' ) ),
			esc_html( number_format_i18n( $minutes ) )
		);
	}
}

/* -------------------------------------------------------------------------
 * Breadcrumbs
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'lienzo_breadcrumbs_enabled' ) ) {
	/**
	 * Whether breadcrumbs should render.
	 *
	 * @return bool
	 */
	function lienzo_breadcrumbs_enabled() {
		return (bool) apply_filters( 'lienzo_enable_breadcrumbs', true );
	}
}

if ( ! function_exists( 'lienzo_breadcrumb_trail' ) ) {
	/**
	 * Breadcrumb trail data for the current view, as label/url pairs.
	 *
	 * Shared by the HTML renderer (lienzo_breadcrumbs()) and the JSON-LD
	 * BreadcrumbList in the SEO module.
	 *
	 * @return array<int,array{label:string,url:string}> Empty on the front page / when disabled.
	 */
	function lienzo_breadcrumb_trail() {
		if ( ! lienzo_breadcrumbs_enabled() || is_front_page() ) {
			return [];
		}

		$trail = [
			[
				'label' => esc_html__( 'Home', 'lienzo-astra' ),
				'url'   => home_url( '/' ),
			],
		];

		if ( is_singular() ) {
			$post = get_queried_object();

			if ( 'post' === $post->post_type ) {
				$categories = get_the_category( $post->ID );
				if ( ! empty( $categories ) ) {
					$trail[] = [
						'label' => $categories[0]->name,
						'url'   => get_category_link( $categories[0] ),
					];
				}
			} elseif ( 'page' === $post->post_type && $post->post_parent ) {
				foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
					$trail[] = [
						'label' => get_the_title( $ancestor_id ),
						'url'   => get_permalink( $ancestor_id ),
					];
				}
			}

			$trail[] = [
				'label' => get_the_title( $post ),
				'url'   => '',
			];
		} elseif ( is_search() ) {
			$trail[] = [
				/* translators: %s: search query. */
				'label' => sprintf( esc_html__( 'Search results for &#8220;%s&#8221;', 'lienzo-astra' ), get_search_query() ),
				'url'   => '',
			];
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$trail[] = [
				'label' => single_term_title( '', false ),
				'url'   => '',
			];
		} elseif ( is_post_type_archive() ) {
			$trail[] = [
				'label' => post_type_archive_title( '', false ),
				'url'   => '',
			];
		} elseif ( is_404() ) {
			$trail[] = [
				'label' => esc_html__( 'Page not found', 'lienzo-astra' ),
				'url'   => '',
			];
		} elseif ( is_home() ) {
			$trail[] = [
				'label' => esc_html__( 'Blog', 'lienzo-astra' ),
				'url'   => '',
			];
		}

		return apply_filters( 'lienzo_breadcrumbs_trail', $trail );
	}
}

if ( ! function_exists( 'lienzo_breadcrumbs' ) ) {
	/**
	 * Build a simple, dependency-free breadcrumb trail for the current view.
	 *
	 * @return string HTML, or an empty string on the front page / when disabled.
	 */
	function lienzo_breadcrumbs() {
		$trail = lienzo_breadcrumb_trail();

		if ( count( $trail ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="lienzo-breadcrumbs" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'lienzo-astra' ); ?>">
			<ol>
				<?php
				$last = count( $trail ) - 1;
				foreach ( $trail as $index => $crumb ) :
					?>
					<li>
						<?php if ( '' !== $crumb['url'] && $index !== $last ) : ?>
							<a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
						<?php else : ?>
							<span aria-current="page"><?php echo esc_html( $crumb['label'] ); ?></span>
						<?php endif; ?>
					</li>
					<?php
				endforeach;
				?>
			</ol>
		</nav>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lienzo_breadcrumbs_shortcode' ) ) {
	/**
	 * `[lienzo_breadcrumbs]` shortcode wrapper, handy inside an Elementor
	 * Shortcode/Text widget when you don't want the dedicated widget.
	 *
	 * @return string
	 */
	function lienzo_breadcrumbs_shortcode() {
		return lienzo_breadcrumbs();
	}
}
add_shortcode( 'lienzo_breadcrumbs', 'lienzo_breadcrumbs_shortcode' );
