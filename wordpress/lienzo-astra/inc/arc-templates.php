<?php
/**
 * ARC Starter Templates integration.
 *
 * The "ARC Starter Templates" plugin imports complete Tailwind-designed
 * pages into this site. This module lets the theme cooperate with it
 * without a hard dependency:
 *
 * - Detects pages that render an ARC template (imported pages carry the
 *   `_arc_st_slug` meta; manually inserted patterns keep the `arc-tpl`
 *   marker class in the content).
 * - Lets the rest of the theme step aside on those pages (boxed container,
 *   reset styles, page title/breadcrumbs/comments and the floating
 *   feature buttons would all fight the imported design).
 * - Lists the imported ARC pages on Appearance > Lienzo Astra with links
 *   back to the plugin's starter-templates screen.
 *
 * Detection is by convention, not dependency — every helper returns false
 * or an empty value when the plugin is absent.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_is_arc_template_page' ) ) {
	/**
	 * Whether the current singular view renders an ARC starter template.
	 *
	 * Mirrors the plugin's own detection (Arc_ST_Assets::page_has_template):
	 * imported pages carry the `_arc_st_slug` meta; block patterns inserted
	 * by hand keep the `arc-tpl` marker in post_content.
	 *
	 * @return bool
	 */
	function lienzo_is_arc_template_page() {
		if ( ! is_singular() ) {
			return false;
		}

		// The plugin serves the Tailwind design system for this markup. When
		// it is inactive the theme must NOT step aside — otherwise imported
		// pages render with neither the plugin's styles nor the theme's.
		if ( ! lienzoastra_arc_st_active() ) {
			return false;
		}

		$post = get_post();
		if ( ! $post ) {
			return false;
		}

		if ( get_post_meta( $post->ID, '_arc_st_slug', true ) ) {
			return true;
		}

		return false !== strpos( (string) $post->post_content, 'arc-tpl' );
	}
}

if ( ! function_exists( 'lienzoastra_arc_st_active' ) ) {
	/**
	 * Whether the ARC Starter Templates plugin is active.
	 *
	 * @return bool
	 */
	function lienzoastra_arc_st_active() {
		return defined( 'ARC_ST_VERSION' ) || class_exists( 'Arc_ST_Plugin' );
	}
}

if ( ! function_exists( 'lienzoastra_arc_st_plugin_file' ) ) {
	/**
	 * The ARC Starter Templates plugin file (e.g.
	 * "arc-starter-templates/arc-starter-templates.php"), or an empty
	 * string when it is not installed. Resolved dynamically so the
	 * connection survives a different plugin folder name.
	 *
	 * @return string
	 */
	function lienzoastra_arc_st_plugin_file() {
		$candidate = 'arc-starter-templates/arc-starter-templates.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $candidate ) ) {
			return $candidate;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( (array) get_plugins() as $file => $data ) {
			if ( isset( $data['Name'] ) && false !== stripos( $data['Name'], 'ARC Starter Templates' ) ) {
				return $file;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'lienzoastra_arc_st_override_dir' ) ) {
	/**
	 * Theme directory the ARC Starter Templates plugin checks for template
	 * overrides (arc-starter-templates/<demo>/<page>.html — child theme is
	 * checked first by the plugin's locate_template() call, then the parent).
	 * A file placed there replaces the plugin's bundled copy on preview,
	 * import and pattern registration.
	 *
	 * @return string Absolute path (may not exist yet).
	 */
	function lienzoastra_arc_st_override_dir() {
		return LIENZOASTRA_DIR . '/arc-starter-templates';
	}
}

if ( ! function_exists( 'lienzoastra_arc_st_overrides' ) ) {
	/**
	 * Template override files this theme currently ships.
	 *
	 * @return array<int,string> File paths relative to the theme root.
	 */
	function lienzoastra_arc_st_overrides() {
		$dir = lienzoastra_arc_st_override_dir();
		if ( ! is_dir( $dir ) ) {
			return [];
		}

		$files = [];
		foreach ( (array) glob( $dir . '/*/*.html' ) as $file ) {
			$files[] = 'arc-starter-templates/' . basename( dirname( $file ) ) . '/' . basename( $file );
		}
		foreach ( (array) glob( $dir . '/*.html' ) as $file ) {
			$files[] = 'arc-starter-templates/' . basename( $file );
		}

		sort( $files );
		return $files;
	}
}

if ( ! function_exists( 'lienzoastra_arc_st_pages' ) ) {
	/**
	 * Pages imported by ARC Starter Templates, read from the plugin's own
	 * `arc_st_page_map` option (slug => page_id).
	 *
	 * @return array<int,array{slug:string,title:string,view:string,edit:string}>
	 */
	function lienzoastra_arc_st_pages() {
		$map = get_option( 'arc_st_page_map', [] );
		$map = is_array( $map ) ? $map : [];

		$pages = [];
		foreach ( $map as $slug => $page_id ) {
			$page_id = (int) $page_id;
			if ( ! $page_id || ! get_post( $page_id ) ) {
				continue;
			}

			$view = get_permalink( $page_id );
			$edit = get_edit_post_link( $page_id, '' );

			$pages[] = [
				'slug'  => (string) $slug,
				'title' => get_the_title( $page_id ),
				'view'  => $view ? $view : '',
				'edit'  => $edit ? $edit : '',
			];
		}

		return $pages;
	}
}

if ( ! function_exists( 'lienzoastra_render_arc_starter_sites' ) ) {
	/**
	 * Print the ARC Starter Templates block on Appearance > Lienzo Astra.
	 * Rendered above the (optional) PAge section — ARC is this site's
	 * starter provider.
	 *
	 * @return void
	 */
	function lienzoastra_render_arc_starter_sites() {
		?>
		<hr>
		<h2><?php esc_html_e( 'ARC Starter Templates', 'lienzo-astra' ); ?></h2>

		<?php if ( lienzoastra_arc_st_active() ) : ?>
			<p>
				<?php esc_html_e( 'The ARC Starter Templates plugin imports complete Tailwind-designed pages as native Gutenberg blocks. Edit them in the block editor like any other page — the demo palette and Outfit font are synced into the block controls via theme.json, and the theme automatically steps aside on imported pages: full-width canvas, no page title, breadcrumbs, comments or floating buttons.', 'lienzo-astra' ); ?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: theme-relative override path. */
					esc_html__( 'Template source files can be overridden from the theme — drop a modified copy at %s and re-import from the wizard. The theme version wins over the bundled one.', 'lienzo-astra' ),
					'<code>arc-starter-templates/&lt;demo&gt;/&lt;page&gt;.html</code>'
				);
				?>
			</p>

			<?php
			$overrides = lienzoastra_arc_st_overrides();
			if ( $overrides ) :
				?>
				<p><strong><?php esc_html_e( 'Active theme overrides:', 'lienzo-astra' ); ?></strong></p>
				<ul style="list-style:disc;padding-inline-start:1.5rem;">
					<?php foreach ( $overrides as $file ) : ?>
						<li><code><?php echo esc_html( $file ); ?></code></li>
					<?php endforeach; ?>
				</ul>
				<?php
			endif;

			$pages = lienzoastra_arc_st_pages();
			if ( empty( $pages ) ) :
				?>
				<p><?php esc_html_e( 'No pages imported yet — run the import wizard to create the ARC site.', 'lienzo-astra' ); ?></p>
				<?php
			else :
				?>
				<ul style="list-style:disc;padding-inline-start:1.5rem;">
					<?php foreach ( $pages as $page ) : ?>
						<li>
							<?php if ( '' !== $page['view'] ) : ?>
								<a href="<?php echo esc_url( $page['view'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $page['title'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $page['title'] ); ?>
							<?php endif; ?>
							<?php if ( '' !== $page['edit'] ) : ?>
								— <a href="<?php echo esc_url( $page['edit'] ); ?>"><?php esc_html_e( 'Edit', 'lienzo-astra' ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php
			endif;
			?>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=arc-starter-templates' ) ); ?>">
					<?php esc_html_e( 'Open ARC Starter Templates', 'lienzo-astra' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'The ARC Starter Templates plugin is not active. Activate it to import the eight-page Ash River Collective site into this theme.', 'lienzo-astra' ); ?>
			</p>
			<?php
			$plugin_file = lienzoastra_arc_st_plugin_file();
			if ( '' !== $plugin_file ) :
				$activate_url = wp_nonce_url(
					self_admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
					'activate-plugin_' . $plugin_file
				);
				?>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( $activate_url ); ?>">
						<?php esc_html_e( 'Activate ARC Starter Templates', 'lienzo-astra' ); ?>
					</a>
				</p>
				<?php
			endif;
			?>
		<?php endif; ?>
		<?php
	}
}
add_action( 'lienzoastra_settings_after_form', 'lienzoastra_render_arc_starter_sites', 5 );
