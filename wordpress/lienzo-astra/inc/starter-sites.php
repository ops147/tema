<?php
/**
 * Starter sites — integration with the PAge plugin.
 *
 * PAge ("PAge — Cloned Site Templates") serves the theme's default import
 * pages: complete starter sites rendered as standalone templates. This
 * module detects it, lists every registered site on Appearance > Lienzo
 * Astra with direct links, and lets the rest of the theme know whether
 * imported starter pages are available.
 *
 * Nothing here runs when PAge is inactive.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzoastra_page_clones_active' ) ) {
	/**
	 * Whether the PAge cloned-sites plugin is active.
	 *
	 * @return bool
	 */
	function lienzoastra_page_clones_active() {
		return class_exists( 'PageClones\\Plugin' ) && defined( 'PAGE_CLONES_OPTION' );
	}
}

if ( ! function_exists( 'lienzoastra_page_clones_plugin_file' ) ) {
	/**
	 * PAge's plugin file (e.g. "PAge/page.php"), or an empty string when it
	 * is not installed. Resolved dynamically so the connection survives a
	 * different plugin folder name on another site.
	 *
	 * @return string
	 */
	function lienzoastra_page_clones_plugin_file() {
		$candidate = 'PAge/page.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $candidate ) ) {
			return $candidate;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( (array) get_plugins() as $file => $data ) {
			if ( isset( $data['Name'] ) && preg_match( '/^PAge\b.*?Cloned/i', $data['Name'] ) ) {
				return $file;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lienzoastra_starter_page_url' ) ) {
	/**
	 * Permalink of a cloned page (site + slug), or an empty string when the
	 * page has not been created yet by PAge's installer.
	 *
	 * @param array  $map  PAge page map (page_id => [site, slug]).
	 * @param string $site Site key.
	 * @param string $slug Page slug.
	 *
	 * @return string
	 */
	function lienzoastra_starter_page_url( $map, $site, $slug ) {
		foreach ( $map as $pid => $meta ) {
			if ( isset( $meta['site'], $meta['slug'] ) && $meta['site'] === $site && $meta['slug'] === $slug ) {
				$permalink = get_permalink( (int) $pid );

				return $permalink ? $permalink : '';
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lienzoastra_starter_sites' ) ) {
	/**
	 * Every starter site registered by PAge, with view URLs per page.
	 *
	 * @return array<int,array{key:string,title:string,pages:array<int,array{slug:string,title:string,url:string}>}>
	 */
	function lienzoastra_starter_sites() {
		if ( ! lienzoastra_page_clones_active() || ! class_exists( 'PageClones\\SiteRepository' ) ) {
			return [];
		}

		$repo  = new \PageClones\SiteRepository();
		$map   = get_option( PAGE_CLONES_OPTION, [] );
		$map   = is_array( $map ) ? $map : [];
		$sites = [];

		foreach ( $repo->sites() as $key => $site ) {
			$pages = [];

			foreach ( (array) $site['pages'] as $slug => $title ) {
				$pages[] = [
					'slug'  => $slug,
					'title' => $title,
					'url'   => lienzoastra_starter_page_url( $map, $key, $slug ),
				];
			}

			$sites[] = [
				'key'   => $key,
				'title' => $site['title'],
				'pages' => $pages,
			];
		}

		return $sites;
	}
}

if ( ! function_exists( 'lienzoastra_render_starter_sites' ) ) {
	/**
	 * Print the "Starter sites" block on Appearance > Lienzo Astra.
	 *
	 * @return void
	 */
	function lienzoastra_render_starter_sites() {
		// ARC Starter Templates is this site's starter provider — when it is
		// active, don't nag about PAge (its section renders only if PAge
		// itself is active below).
		if ( ! lienzoastra_page_clones_active() && function_exists( 'lienzoastra_arc_st_active' ) && lienzoastra_arc_st_active() ) {
			return;
		}
		?>
		<hr>
		<h2><?php esc_html_e( 'Starter sites', 'lienzo-astra' ); ?></h2>
		<?php if ( lienzoastra_page_clones_active() ) : ?>
			<p>
				<?php esc_html_e( 'Default import pages are served by the PAge plugin as standalone cloned sites. Edit their text and images under Appearance > Customize > PAge, or manage them on the Cloned Pages screen.', 'lienzo-astra' ); ?>
			</p>

			<?php
			$sites = lienzoastra_starter_sites();
			if ( empty( $sites ) ) :
				?>
				<p><?php esc_html_e( 'No starter sites registered yet — use "Rebuild pages" on the Cloned Pages screen to create them.', 'lienzo-astra' ); ?></p>
				<?php
			else :
				foreach ( $sites as $site ) :
					?>
					<h3><?php echo esc_html( $site['title'] ); ?></h3>
					<ul style="list-style:disc;padding-inline-start:1.5rem;">
						<?php foreach ( $site['pages'] as $page ) : ?>
							<li>
								<?php if ( '' !== $page['url'] ) : ?>
									<a href="<?php echo esc_url( $page['url'] ); ?>" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html( $page['title'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $page['title'] ); ?>
									<em><?php esc_html_e( '(page not created yet — rebuild from Cloned Pages)', 'lienzo-astra' ); ?></em>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
				endforeach;
			endif;
			?>

			<p>
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=page-clones' ) ); ?>">
					<?php esc_html_e( 'Open Cloned Pages', 'lienzo-astra' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'The default import pages (complete starter sites) are provided by the PAge plugin. Activate it to create and manage them.', 'lienzo-astra' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}
}
add_action( 'lienzoastra_settings_after_form', 'lienzoastra_render_starter_sites' );
