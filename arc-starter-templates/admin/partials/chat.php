<?php
/**
 * Chat Bot screen — editable bot settings + keyword answers on top,
 * conversation monitor (grouped by visitor session) below.
 *
 * Variables: $settings (array), $paged (int).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

$conversations = Arc_ST_Chat::conversations( $paged );
$total         = Arc_ST_Chat::conversation_count();
$pages         = max( 1, (int) ceil( $total / 20 ) );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Chat Bot', 'arc-starter-templates' ); ?></h1>
	<hr class="wp-header-end" />

	<?php if ( isset( $_GET['arc_chat_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Chat settings saved.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['arc_chat_cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Conversation log cleared.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['arc_chat_deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Conversation deleted.', 'arc-starter-templates' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'arc_st_chat_save' ); ?>
		<input type="hidden" name="action" value="arc_st_chat_save" />

		<h2><?php esc_html_e( 'Bot settings', 'arc-starter-templates' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enabled', 'arc-starter-templates' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="chat_enabled" value="1" <?php checked( (int) $settings['enabled'], 1 ); ?> />
						<?php esc_html_e( 'Show the floating chat on imported template pages', 'arc-starter-templates' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="chat_title"><?php esc_html_e( 'Chat title', 'arc-starter-templates' ); ?></label></th>
				<td><input type="text" id="chat_title" name="chat_title" class="regular-text" value="<?php echo esc_attr( $settings['title'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="chat_greeting"><?php esc_html_e( 'Greeting message', 'arc-starter-templates' ); ?></label></th>
				<td><textarea id="chat_greeting" name="chat_greeting" class="large-text" rows="2"><?php echo esc_textarea( $settings['greeting'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'First message the visitor sees when the chat opens.', 'arc-starter-templates' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="chat_fallback"><?php esc_html_e( 'Fallback answer', 'arc-starter-templates' ); ?></label></th>
				<td><textarea id="chat_fallback" name="chat_fallback" class="large-text" rows="2"><?php echo esc_textarea( $settings['fallback'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Sent when no keyword matches the visitor message.', 'arc-starter-templates' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Default answers', 'arc-starter-templates' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Each row maps keywords (comma separated) to the reply the bot sends. Matching is case-insensitive; the row with the most keyword hits wins.', 'arc-starter-templates' ); ?></p>
		<table class="widefat striped" id="arc-chat-pairs" style="max-width:960px">
			<thead>
				<tr>
					<th style="width:38%"><?php esc_html_e( 'Keywords', 'arc-starter-templates' ); ?></th>
					<th><?php esc_html_e( 'Answer', 'arc-starter-templates' ); ?></th>
					<th style="width:40px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $settings['pairs'] as $pair ) : ?>
				<tr>
					<td><input type="text" name="chat_kw[]" class="large-text" value="<?php echo esc_attr( $pair['kw'] ); ?>" placeholder="price, pricing, cost" /></td>
					<td><textarea name="chat_a[]" class="large-text" rows="2"><?php echo esc_textarea( $pair['a'] ); ?></textarea></td>
					<td><button type="button" class="button arc-chat-del" aria-label="<?php esc_attr_e( 'Remove', 'arc-starter-templates' ); ?>">&times;</button></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="arc-chat-add">+ <?php esc_html_e( 'Add answer', 'arc-starter-templates' ); ?></button></p>

		<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save chat settings', 'arc-starter-templates' ); ?></button></p>
	</form>

	<hr />
	<h2 class="wp-heading-inline"><?php esc_html_e( 'Conversations', 'arc-starter-templates' ); ?></h2>
	<span style="margin-left:8px;color:#646970">
		<?php echo esc_html( sprintf( /* translators: %d: conversation count. */ __( '%d sessions', 'arc-starter-templates' ), $total ) ); ?>
	</span>
	<a class="button button-link-delete" style="float:right" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_chat_clear' ), 'arc_st_chat_clear' ) ); ?>"
		onclick="return confirm('<?php echo esc_js( __( 'Delete ALL chat logs? This cannot be undone.', 'arc-starter-templates' ) ); ?>');">
		<?php esc_html_e( 'Clear all', 'arc-starter-templates' ); ?>
	</a>

	<?php if ( empty( $conversations ) ) : ?>
		<p><?php esc_html_e( 'No conversations yet — they appear here once visitors use the chat on an imported page.', 'arc-starter-templates' ); ?></p>
	<?php else : ?>
		<table class="widefat striped" style="max-width:960px">
			<thead>
				<tr>
					<th style="width:160px"><?php esc_html_e( 'Session', 'arc-starter-templates' ); ?></th>
					<th style="width:150px"><?php esc_html_e( 'Last activity', 'arc-starter-templates' ); ?></th>
					<th style="width:180px"><?php esc_html_e( 'Page', 'arc-starter-templates' ); ?></th>
					<th><?php esc_html_e( 'Messages', 'arc-starter-templates' ); ?></th>
					<th style="width:60px"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $conversations as $c ) : ?>
				<tr>
					<td><code><?php echo esc_html( substr( $c['session'], 0, 12 ) ); ?></code></td>
					<td><?php echo esc_html( $c['last'] ); ?></td>
					<td style="word-break:break-all"><?php echo esc_html( wp_parse_url( (string) $c['page'], PHP_URL_PATH ) ?: $c['page'] ); ?></td>
					<td>
						<details>
							<summary><?php echo esc_html( sprintf( /* translators: %d: lines. */ __( '%d messages', 'arc-starter-templates' ), (int) $c['msg_count'] ) ); ?></summary>
							<div style="padding:8px 0">
								<?php foreach ( $c['messages'] as $msg ) : ?>
									<p style="margin:4px 0">
										<strong style="color:<?php echo 'visitor' === $msg['role'] ? '#2271b1' : '#1d7a4f'; ?>">
											<?php echo 'visitor' === $msg['role'] ? esc_html__( 'Visitor', 'arc-starter-templates' ) : esc_html__( 'Bot', 'arc-starter-templates' ); ?>:
										</strong>
										<?php echo esc_html( $msg['message'] ); ?>
										<em style="color:#a7aaad"><?php echo esc_html( $msg['created'] ); ?></em>
									</p>
								<?php endforeach; ?>
							</div>
						</details>
					</td>
					<td>
						<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_chat_delete&session=' . $c['session'] ), 'arc_st_chat_delete_' . $c['session'] ) ); ?>"
							onclick="return confirm('<?php echo esc_js( __( 'Delete this conversation?', 'arc-starter-templates' ) ); ?>');">&times;</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			?>
		</div></div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<script>
(function () {
	const tbody = document.querySelector('#arc-chat-pairs tbody');
	document.getElementById('arc-chat-add').addEventListener('click', function () {
		const tr = document.createElement('tr');
		tr.innerHTML = '<td><input type="text" name="chat_kw[]" class="large-text" placeholder="price, pricing, cost" /></td>' +
			'<td><textarea name="chat_a[]" class="large-text" rows="2"></textarea></td>' +
			'<td><button type="button" class="button arc-chat-del">&times;</button></td>';
		tbody.appendChild(tr);
	});
	tbody.addEventListener('click', function (e) {
		if (e.target.classList.contains('arc-chat-del')) {
			e.target.closest('tr').remove();
		}
	});
})();
</script>
