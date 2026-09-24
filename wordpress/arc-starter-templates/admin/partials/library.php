<?php
/**
 * Starter Templates screen — solace-extra style layout, one card per demo.
 *
 * Variables: $demos (array id=>card), $elementor (bool), $imported (array slug=>id),
 *            $report (array), $settings (array), $pages (array).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

$saved = isset( $_GET['arc_saved'] ) && '1' === $_GET['arc_saved']; // phpcs:ignore WordPress.Security.NonceVerification

// Category filter: union of every demo's categories (first-seen order).
$all_cats = array();
foreach ( (array) $demos as $d ) {
	foreach ( (array) $d['categories'] as $cat ) {
		if ( ! in_array( $cat, $all_cats, true ) ) {
			$all_cats[] = $cat;
		}
	}
}
?>
<div class="wrap arc-st">
	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['arc_removed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Imported site removed.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>

	<section class="arc-st-hero">
		<div class="arc-st-hero-text">
			<h2>
				<?php esc_html_e( 'Get Started With Our', 'arc-starter-templates' ); ?>
				<span><?php esc_html_e( 'Starter Templates', 'arc-starter-templates' ); ?></span>
			</h2>
			<p><?php esc_html_e( 'Import a complete, ready-to-edit website in a few clicks.', 'arc-starter-templates' ); ?></p>
		</div>
		<div class="arc-st-hero-badge <?php echo $elementor ? 'is-on' : ''; ?>">
			<?php echo $elementor ? esc_html__( 'Elementor ready', 'arc-starter-templates' ) : esc_html__( 'Classic mode (Elementor not detected)', 'arc-starter-templates' ); ?>
		</div>
	</section>

	<div class="arc-st-main">
		<aside class="arc-st-side">
			<span class="arc-st-side-title"><?php esc_html_e( 'Pick your template', 'arc-starter-templates' ); ?></span>
			<span class="arc-st-side-desc">
				<?php esc_html_e( 'Search in', 'arc-starter-templates' ); ?>
				<strong class="arc-st-count"><?php echo (int) count( $demos ); ?></strong>
				<?php esc_html_e( 'bundled templates', 'arc-starter-templates' ); ?>
			</span>
			<div class="arc-st-search">
				<input type="text" class="arc-st-search-input" placeholder="<?php esc_attr_e( 'Search', 'arc-starter-templates' ); ?>" aria-label="<?php esc_attr_e( 'Search templates', 'arc-starter-templates' ); ?>" />
			</div>
			<span class="arc-st-side-cat"><?php esc_html_e( 'Categories', 'arc-starter-templates' ); ?></span>
			<div class="arc-st-cats">
				<?php foreach ( $all_cats as $cat ) : ?>
					<label class="arc-st-cat">
						<input type="checkbox" value="<?php echo esc_attr( $cat ); ?>" checked />
						<?php echo esc_html( $cat ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php if ( ! $elementor ) : ?>
				<div class="arc-st-note">
					<?php esc_html_e( 'Tip: install Elementor to edit the imported pages visually (texts & images). Without it, pages are imported as native Gutenberg blocks — editable in the block editor inside your theme.', 'arc-starter-templates' ); ?>
				</div>
			<?php endif; ?>

			<?php $overrides = Arc_ST_Templates::overrides(); ?>
			<?php if ( $overrides ) : ?>
				<details class="arc-st-settings">
					<summary><?php esc_html_e( 'Theme template overrides', 'arc-starter-templates' ); ?></summary>
					<p class="description">
						<?php esc_html_e( 'The active theme replaces these bundled templates (arc-starter-templates/ folder inside the theme). Re-import to apply edits.', 'arc-starter-templates' ); ?>
					</p>
					<ul style="margin:0;list-style:disc;padding-inline-start:1.2rem;">
						<?php foreach ( $overrides as $slug => $path ) : ?>
							<li><code><?php echo esc_html( $slug ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>

			<details class="arc-st-settings">
				<summary><?php esc_html_e( 'Contact form settings', 'arc-starter-templates' ); ?></summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="arc_st_settings" />
					<?php wp_nonce_field( 'arc_st_settings' ); ?>
					<p>
						<label for="arc-st-email"><strong><?php esc_html_e( 'Recipient email', 'arc-starter-templates' ); ?></strong></label>
						<input type="email" id="arc-st-email" name="arc_st_contact_email" class="widefat" value="<?php echo esc_attr( $settings['contact_email'] ); ?>" />
					</p>
					<p>
						<label for="arc-st-consent"><strong><?php esc_html_e( 'Consent checkbox text', 'arc-starter-templates' ); ?></strong></label>
						<input type="text" id="arc-st-consent" name="arc_st_consent_text" class="widefat" value="<?php echo esc_attr( $settings['consent_text'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. I agree to the privacy policy', 'arc-starter-templates' ); ?>" />
						<span class="description"><?php esc_html_e( 'Empty = no consent checkbox. When set, the contact form requires it.', 'arc-starter-templates' ); ?></span>
					</p>
					<p><button type="submit" class="button"><?php esc_html_e( 'Save settings', 'arc-starter-templates' ); ?></button></p>
				</form>
			</details>

			<details class="arc-st-settings">
				<summary><?php esc_html_e( 'Templates repository', 'arc-starter-templates' ); ?></summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="arc_st_settings" />
					<?php wp_nonce_field( 'arc_st_settings' ); ?>
					<p>
						<label for="arc-st-remote"><strong><?php esc_html_e( 'Remote base URL', 'arc-starter-templates' ); ?></strong></label>
						<input type="url" id="arc-st-remote" name="arc_st_remote_base" class="widefat" value="<?php echo esc_attr( $settings['remote_base'] ); ?>" placeholder="https://raw.githubusercontent.com/org/repo/branch" />
						<span class="description">
							<?php esc_html_e( 'Directory that mirrors the plugin layout: templates/manifest.json, templates/<demo>/<page>.html and assets/img/*. For GitHub use the raw URL (https://raw.githubusercontent.com/<org>/<repo>/<branch>). Empty = bundled templates.', 'arc-starter-templates' ); ?>
						</span>
					</p>
					<p>
						<label for="arc-st-token"><strong><?php esc_html_e( 'Access token (private repo)', 'arc-starter-templates' ); ?></strong></label>
						<input type="password" id="arc-st-token" name="arc_st_remote_token" class="widefat" value="" autocomplete="off" placeholder="<?php echo esc_attr( $settings['token_set'] ? __( 'Token configured — enter a new one to replace', 'arc-starter-templates' ) : __( 'GitHub personal access token (repo scope)', 'arc-starter-templates' ) ); ?>" />
						<span class="description">
							<?php esc_html_e( 'Only needed for private repositories. Sent as a Bearer header when downloading. Empty = keep the current token.', 'arc-starter-templates' ); ?>
						</span>
					</p>
					<?php if ( $settings['token_set'] ) : ?>
						<p>
							<label><input type="checkbox" name="arc_st_remote_token_clear" value="1" /> <?php esc_html_e( 'Remove the stored token', 'arc-starter-templates' ); ?></label>
						</p>
					<?php endif; ?>
					<?php if ( '' !== $settings['remote_base'] ) : ?>
						<p class="description">
							<?php esc_html_e( 'Mode: remote — templates and images download from the repository on demand and are cached under uploads/arc-st-remote/. Every import pulls the current repository state.', 'arc-starter-templates' ); ?>
						</p>
					<?php endif; ?>
					<p><button type="submit" class="button"><?php esc_html_e( 'Save settings', 'arc-starter-templates' ); ?></button></p>
				</form>
			</details>

			<?php if ( $imported ) : ?>
				<details class="arc-st-danger">
					<summary><?php esc_html_e( 'Danger zone', 'arc-starter-templates' ); ?></summary>
					<a class="button arc-st-remove-site"
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_remove' ), 'arc_st_remove' ) ); ?>">
						<?php esc_html_e( 'Remove all imported sites', 'arc-starter-templates' ); ?>
					</a>
					<span class="description"><?php esc_html_e( 'Deletes every imported page and plugin-created menu across all demos. Media files are kept.', 'arc-starter-templates' ); ?></span>
				</details>
			<?php endif; ?>
		</aside>

		<main class="arc-st-grid">
			<?php foreach ( $demos as $demo ) :
				$demo_imported = count( array_intersect( (array) $demo['pages'], array_keys( $imported ) ) );
				$home_id       = ! empty( $imported[ $demo['home'] ] ) ? (int) $imported[ $demo['home'] ] : 0;
				?>
				<div class="demo arc-st-demo" tabindex="0" role="button" aria-label="<?php echo esc_attr( $demo['title'] ); ?>"
					data-name="<?php echo esc_attr( $demo['title'] ); ?>"
					data-cats="<?php echo esc_attr( implode( ' ', $demo['categories'] ) ); ?>"
					data-wizard-url="<?php echo esc_url( $demo['wizard_url'] ); ?>">
					<div class="box-image">
						<img src="<?php echo esc_url( $demo['image'] ); ?>" alt="<?php echo esc_attr( $demo['title'] ); ?>" />
						<a class="arc-st-preview" href="<?php echo esc_url( $demo['preview_url'] ); ?>" target="_blank" rel="noopener" data-preview="<?php echo esc_url( $demo['preview_url'] ); ?>">
							<?php esc_html_e( 'Preview', 'arc-starter-templates' ); ?>
						</a>
					</div>
					<div class="box-content">
						<div class="top-content">
							<span class="title"><?php echo esc_html( $demo['title'] ); ?></span>
							<span class="label-free"><?php esc_html_e( 'Free', 'arc-starter-templates' ); ?></span>
							<?php if ( $demo_imported ) : ?>
								<span class="label-imported">✔ <?php echo (int) $demo_imported; ?> <?php esc_html_e( 'imported', 'arc-starter-templates' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="bottom-content">
							<p><strong><?php esc_html_e( 'Ideal for: ', 'arc-starter-templates' ); ?></strong><?php echo esc_html( $demo['desc'] ); ?></p>
						</div>
						<div class="arc-st-card-actions">
							<a class="button arc-st-preview-btn" href="<?php echo esc_url( $demo['preview_url'] ); ?>" target="_blank" rel="noopener" data-preview="<?php echo esc_url( $demo['preview_url'] ); ?>">
								<?php esc_html_e( 'Preview', 'arc-starter-templates' ); ?>
							</a>
							<a class="button button-primary arc-st-open-wizard"
								href="<?php echo esc_url( $demo['wizard_url'] ); ?>">
								<?php echo $demo_imported ? esc_html__( 'Re-import Site', 'arc-starter-templates' ) : esc_html__( 'Import Site', 'arc-starter-templates' ); ?>
							</a>
							<?php if ( $home_id ) : ?>
								<a class="button" href="<?php echo esc_url( get_permalink( $home_id ) ); ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'View', 'arc-starter-templates' ); ?>
								</a>
								<a class="button" href="<?php echo esc_url( get_edit_post_link( $home_id ) ); ?>">
									<?php esc_html_e( 'Edit', 'arc-starter-templates' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			<?php if ( ! empty( $report['time'] ) ) : ?>
				<section class="arc-st-report">
					<h3><?php esc_html_e( 'Last import', 'arc-starter-templates' ); ?></h3>
					<ul>
						<li><strong><?php esc_html_e( 'When:', 'arc-starter-templates' ); ?></strong> <?php echo esc_html( $report['time'] ); ?></li>
						<li><strong><?php esc_html_e( 'Mode:', 'arc-starter-templates' ); ?></strong> <?php echo esc_html( isset( $report['mode'] ) ? $report['mode'] : '—' ); ?></li>
						<li><strong><?php esc_html_e( 'Status:', 'arc-starter-templates' ); ?></strong> <?php echo esc_html( isset( $report['status'] ) ? $report['status'] : '—' ); ?></li>
						<li><strong><?php esc_html_e( 'Pages:', 'arc-starter-templates' ); ?></strong> <?php echo isset( $report['pages'] ) ? count( (array) $report['pages'] ) : 0; ?></li>
						<li><strong><?php esc_html_e( 'Images:', 'arc-starter-templates' ); ?></strong> <?php echo isset( $report['images']['imported'] ) ? (int) $report['images']['imported'] : 0; ?></li>
					</ul>
					<?php if ( ! empty( $report['errors'] ) ) : ?>
						<ul class="arc-st-report-errors">
							<?php foreach ( (array) $report['errors'] as $err ) : ?>
								<li><?php echo esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $report['images']['errors'] ) ) : ?>
						<ul class="arc-st-report-errors">
							<?php foreach ( (array) $report['images']['errors'] as $file => $err ) : ?>
								<li><?php echo esc_html( $file . ': ' . $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</main>
	</div>
</div>

<div class="arc-st-modal" id="arc-st-preview-modal" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Template preview', 'arc-starter-templates' ); ?>">
	<div class="arc-st-modal-backdrop" data-close></div>
	<div class="arc-st-modal-frame">
		<div class="arc-st-modal-bar">
			<strong class="arc-st-modal-title"><?php esc_html_e( 'Preview', 'arc-starter-templates' ); ?></strong>
			<span>
				<a href="#" class="arc-st-modal-open" target="_blank" rel="noopener"><?php esc_html_e( 'Open in new tab', 'arc-starter-templates' ); ?></a>
				<button type="button" class="arc-st-modal-close" data-close aria-label="<?php esc_attr_e( 'Close preview', 'arc-starter-templates' ); ?>">✕</button>
			</span>
		</div>
		<iframe class="arc-st-modal-iframe" title="<?php esc_attr_e( 'Template preview', 'arc-starter-templates' ); ?>"></iframe>
	</div>
</div>
