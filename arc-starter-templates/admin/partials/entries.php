<?php
/**
 * Form Entries screen — list, filter, delete and export submissions stored
 * by Arc_ST_Entries (contact + newsletter forms from imported templates).
 *
 * Variables: $rows (array), $total (int), $paged (int), $pages (int),
 *            $form (string filter), $labels (array).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

$base_url = admin_url( 'admin.php?page=' . Arc_ST_Entries::PAGE );
$filters  = array(
	''          => __( 'All', 'arc-starter-templates' ),
	'contact'   => __( 'Contact', 'arc-starter-templates' ),
	'subscribe' => __( 'Newsletter', 'arc-starter-templates' ),
);
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Form Entries', 'arc-starter-templates' ); ?></h1>
	<span style="margin-left:8px;color:#646970">
		<?php echo esc_html( sprintf( /* translators: %d: entry count. */ __( '%d submissions', 'arc-starter-templates' ), $total ) ); ?>
	</span>
	<hr class="wp-header-end" />

	<?php if ( isset( $_GET['arc_entry_deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Entry deleted.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['arc_entries_cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All entries deleted.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>

	<ul class="subsubsub">
		<?php
		$first = true;
		foreach ( $filters as $key => $label ) :
			$url  = '' === $key ? $base_url : add_query_arg( 'form', $key, $base_url );
			$cur  = $form === $key ? 'current' : '';
			$sep  = $first ? '' : ' | ';
			$first = false;
			?>
			<li><?php echo esc_html( $sep ); ?><a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $cur ); ?>"><?php echo esc_html( $label ); ?></a></li>
		<?php endforeach; ?>
	</ul>

	<div class="tablenav top">
		<div class="alignleft actions">
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_entries_csv' ), 'arc_st_entries_csv' ) ); ?>">
				<?php esc_html_e( 'Export CSV', 'arc-starter-templates' ); ?>
			</a>
		</div>
		<div class="alignright">
			<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_entries_clear' ), 'arc_st_entries_clear' ) ); ?>"
				onclick="return confirm('<?php echo esc_js( __( 'Delete ALL form entries? This cannot be undone.', 'arc-starter-templates' ) ); ?>');">
				<?php esc_html_e( 'Clear all', 'arc-starter-templates' ); ?>
			</a>
		</div>
		<?php if ( $pages > 1 ) : ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo esc_html( $total ); ?> <?php esc_html_e( 'items', 'arc-starter-templates' ); ?></span>
				<?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
					<?php if ( $i === $paged ) : ?>
						<span class="tablenav-pages-navspan button disabled"><?php echo (int) $i; ?></span>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $i, 'form' => $form ), $base_url ) ); ?>"><?php echo (int) $i; ?></a>
					<?php endif; ?>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
	</div>

	<table class="widefat striped">
		<thead>
			<tr>
				<th style="width:50px"><?php esc_html_e( 'ID', 'arc-starter-templates' ); ?></th>
				<th style="width:150px"><?php esc_html_e( 'Date', 'arc-starter-templates' ); ?></th>
				<th style="width:100px"><?php esc_html_e( 'Form', 'arc-starter-templates' ); ?></th>
				<th><?php esc_html_e( 'Name', 'arc-starter-templates' ); ?></th>
				<th><?php esc_html_e( 'Email', 'arc-starter-templates' ); ?></th>
				<th><?php esc_html_e( 'Data', 'arc-starter-templates' ); ?></th>
				<th><?php esc_html_e( 'Page', 'arc-starter-templates' ); ?></th>
				<th style="width:70px"></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No entries yet — they appear here when a visitor submits a form on an imported site.', 'arc-starter-templates' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $row ) :
				$data    = (array) json_decode( (string) $row['data'], true );
				$summary = array();
				foreach ( $data as $key => $value ) {
					if ( '' === trim( (string) $value ) ) {
						continue;
					}
					$label     = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
					$summary[] = $label . ': ' . $value;
				}
				$delete_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=arc_st_entry_delete&id=' . (int) $row['id'] ),
					'arc_st_entry_delete_' . (int) $row['id']
				);
				?>
				<tr>
					<td><?php echo (int) $row['id']; ?></td>
					<td><?php echo esc_html( $row['created'] ); ?></td>
					<td><code><?php echo esc_html( $row['form'] ); ?></code></td>
					<td><?php echo esc_html( $row['name'] ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $row['email'] ); ?>"><?php echo esc_html( $row['email'] ); ?></a></td>
					<td style="max-width:340px"><?php echo esc_html( implode( ' — ', $summary ) ); ?></td>
					<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis"><a href="<?php echo esc_url( $row['page'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $row['page'], PHP_URL_PATH ) ? wp_parse_url( $row['page'], PHP_URL_PATH ) : $row['page'] ); ?></a></td>
					<td><a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Delete this entry?', 'arc-starter-templates' ) ); ?>');"><?php esc_html_e( 'Delete', 'arc-starter-templates' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
