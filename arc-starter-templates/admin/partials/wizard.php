<?php
/**
 * Import wizard screen — progress bar + step list + live log, driven by
 * admin/js/import.js through the wp_ajax_arc_st_step_* endpoints.
 *
 * Variables: $demo (array), $elementor (bool), $state (array), $pages (array).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

$resume = ( 'running' === $state['status'] || 'failed' === $state['status'] )
	&& ! empty( $state['started'] );
?>
<div class="wrap arc-st arc-st-wizard-wrap">
	<section class="arc-st-hero">
		<div class="arc-st-hero-text">
			<h2><?php echo esc_html( $demo['title'] ); ?> <span><?php esc_html_e( 'Import', 'arc-starter-templates' ); ?></span></h2>
			<p><?php esc_html_e( 'The wizard creates the site pages, uploads the images and configures the menu — nothing else on the site is touched.', 'arc-starter-templates' ); ?></p>
		</div>
		<div class="arc-st-hero-badge <?php echo $elementor ? 'is-on' : ''; ?>">
			<?php echo $elementor ? esc_html__( 'Elementor mode', 'arc-starter-templates' ) : esc_html__( 'Classic mode', 'arc-starter-templates' ); ?>
		</div>
	</section>

	<?php if ( $resume ) : ?>
		<div class="notice notice-info arc-st-resume-note">
			<p>
				<?php esc_html_e( 'A previous import did not finish.', 'arc-starter-templates' ); ?>
				<strong><?php esc_html_e( 'Resume', 'arc-starter-templates' ); ?></strong>
				<?php esc_html_e( 'continues from where it stopped; Start begins a fresh run.', 'arc-starter-templates' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<section class="arc-st-progress" id="arc-st-wizard">
		<div class="arc-st-progress-head">
			<div class="arc-st-percent" aria-hidden="true">0%</div>
			<div class="arc-st-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="<?php esc_attr_e( 'Import progress', 'arc-starter-templates' ); ?>">
				<div class="arc-st-bar-fill" style="width:0%"></div>
			</div>
		</div>

		<ul class="arc-st-steps">
			<li data-step="reset" class="is-optional"><span class="dot"></span><span class="step-label"><?php esc_html_e( 'Remove previous import', 'arc-starter-templates' ); ?></span><span class="step-state"></span></li>
			<li data-step="media"><span class="dot"></span><span class="step-label"><?php esc_html_e( 'Upload images to Media Library', 'arc-starter-templates' ); ?></span><span class="step-state"></span></li>
			<li data-step="pages"><span class="dot"></span><span class="step-label"><?php esc_html_e( 'Create pages', 'arc-starter-templates' ); ?></span><span class="step-state"></span></li>
			<li data-step="setup"><span class="dot"></span><span class="step-label"><?php esc_html_e( 'Front page, menus & editor settings', 'arc-starter-templates' ); ?></span><span class="step-state"></span></li>
		</ul>

		<ul class="arc-st-log" id="arc-st-log" aria-live="polite" aria-relevant="additions"></ul>

		<div class="arc-st-wizard-start">
			<div class="arc-st-options">
				<fieldset class="arc-st-page-pick">
					<legend><?php esc_html_e( 'Pages to import', 'arc-starter-templates' ); ?></legend>
					<div class="arc-st-page-grid">
						<?php foreach ( $pages as $slug => $meta ) : ?>
							<label class="arc-st-reset-check">
								<input type="checkbox" class="arc-st-page-chk" value="<?php echo esc_attr( $slug ); ?>" checked />
								<?php echo esc_html( $meta['name'] ); ?>
							</label>
						<?php endforeach; ?>
					</div>
					<span class="arc-st-page-tools">
						<button type="button" class="button-link" id="arc-st-pages-all"><?php esc_html_e( 'All', 'arc-starter-templates' ); ?></button>
						·
						<button type="button" class="button-link" id="arc-st-pages-none"><?php esc_html_e( 'None', 'arc-starter-templates' ); ?></button>
					</span>
				</fieldset>
				<p class="arc-st-reset-check description">
					<?php esc_html_e( 'Pages from a previous import are removed automatically.', 'arc-starter-templates' ); ?>
				</p>
				<label class="arc-st-reset-check">
					<input type="checkbox" id="arc-st-front" checked />
					<?php esc_html_e( 'Set the imported Home as the front page', 'arc-starter-templates' ); ?>
				</label>
				<label class="arc-st-reset-check">
					<input type="checkbox" id="arc-st-dry" />
					<?php esc_html_e( 'Dry run — report what would happen without writing anything', 'arc-starter-templates' ); ?>
				</label>
			</div>
			<div class="arc-st-start-btns">
				<button type="button" class="button button-primary button-hero" id="arc-st-start">
					<?php esc_html_e( 'Start Import', 'arc-starter-templates' ); ?>
				</button>
				<?php if ( $resume ) : ?>
					<button type="button" class="button button-hero" id="arc-st-resume">
						<?php esc_html_e( 'Resume Import', 'arc-starter-templates' ); ?>
					</button>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Arc_ST_Admin::PAGE_LIBRARY ) ); ?>">
					<?php esc_html_e( 'Back', 'arc-starter-templates' ); ?>
				</a>
			</div>
		</div>

		<div class="arc-st-wizard-done" hidden tabindex="-1">
			<p class="arc-st-done-text">✔ <?php esc_html_e( 'Your site is ready.', 'arc-starter-templates' ); ?></p>
			<ul class="arc-st-done-pages"></ul>
			<div class="arc-st-done-actions"></div>
		</div>
	</section>
</div>
