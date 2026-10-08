<?php
/**
 * Chat Bot screen — editable bot settings + keyword answers on top,
 * conversation monitor (grouped by visitor session) below with search,
 * lead filter, stats and CSV export.
 *
 * Variables: $settings (array), $paged (int), $search (string),
 * $leads (bool), $stats (array), $ai_down (array|false).
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

$conversations = Arc_ST_Chat::conversations( $paged, 20, $search, $leads );
$total         = Arc_ST_Chat::conversation_count( $search, $leads );
$pages         = max( 1, (int) ceil( $total / 20 ) );
?>
<div class="wrap arc-chat-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Chat Bot', 'arc-starter-templates' ); ?></h1>
	<p class="arc-page-sub"><?php esc_html_e( 'Floating assistant shown on imported template pages.', 'arc-starter-templates' ); ?></p>
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

	<div class="arc-chat-stats">
		<div class="arc-stat">
			<div class="arc-stat__num"><?php echo esc_html( number_format_i18n( $stats['sessions'] ) ); ?></div>
			<div class="arc-stat__label"><?php esc_html_e( 'Sessions', 'arc-starter-templates' ); ?></div>
		</div>
		<div class="arc-stat arc-stat--navy">
			<div class="arc-stat__num"><?php echo esc_html( number_format_i18n( $stats['messages'] ) ); ?></div>
			<div class="arc-stat__label"><?php esc_html_e( 'Messages', 'arc-starter-templates' ); ?></div>
		</div>
		<div class="arc-stat arc-stat--amber">
			<div class="arc-stat__num"><?php echo esc_html( number_format_i18n( $stats['leads'] ) ); ?></div>
			<div class="arc-stat__label"><?php esc_html_e( 'Leads (left email)', 'arc-starter-templates' ); ?></div>
		</div>
		<div class="arc-stat arc-stat--green">
			<div class="arc-stat__num"><?php echo esc_html( number_format_i18n( $stats['today'] ) ); ?></div>
			<div class="arc-stat__label"><?php esc_html_e( 'Messages today', 'arc-starter-templates' ); ?></div>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'arc_st_chat_save' ); ?>
		<input type="hidden" name="action" value="arc_st_chat_save" />

		<div class="arc-chat-card">
			<div class="arc-chat-card__head">
				<h2><?php esc_html_e( 'Bot settings', 'arc-starter-templates' ); ?></h2>
				<p><?php esc_html_e( 'What the widget says and how long conversations are kept.', 'arc-starter-templates' ); ?></p>
			</div>
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
				<tr>
					<th scope="row"><label for="chat_retention"><?php esc_html_e( 'Log retention', 'arc-starter-templates' ); ?></label></th>
					<td>
						<select id="chat_retention" name="chat_retention">
							<?php foreach ( array( 0, 30, 90, 180, 365 ) as $d ) : ?>
							<option value="<?php echo (int) $d; ?>" <?php selected( (int) $settings['retention'], $d ); ?>>
								<?php echo 0 === $d ? esc_html__( 'Keep forever', 'arc-starter-templates' ) : esc_html( sprintf( /* translators: %d: days. */ __( 'Delete after %d days', 'arc-starter-templates' ), $d ) ); ?>
							</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'A daily cron drops log rows older than this. Limiting retention reduces stored visitor data.', 'arc-starter-templates' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="arc-chat-card">
			<div class="arc-chat-card__head">
				<h2><?php esc_html_e( 'AI answers', 'arc-starter-templates' ); ?></h2>
				<p><?php esc_html_e( 'Connect an OpenAI-compatible API so the bot replies with natural, human language about the business. If the provider is unreachable or out of credits, the bot keeps answering with the default answers below.', 'arc-starter-templates' ); ?></p>
			</div>
			<?php if ( $ai_down ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: failure time, 2: provider error. */
							__( 'AI replies are paused — the provider failed at %1$s (%2$s). The bot is answering with the default answers and will retry the API automatically.', 'arc-starter-templates' ),
							isset( $ai_down['since'] ) ? $ai_down['since'] : '',
							isset( $ai_down['reason'] ) ? $ai_down['reason'] : ''
						)
					);
					?>
				</p></div>
			<?php endif; ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'AI provider', 'arc-starter-templates' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="chat_ai_enabled" value="1" <?php checked( (int) $settings['ai_enabled'], 1 ); ?> />
							<?php esc_html_e( 'Answer with the AI — falls back to the default answers on any error', 'arc-starter-templates' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="chat_ai_endpoint"><?php esc_html_e( 'API endpoint', 'arc-starter-templates' ); ?></label></th>
					<td>
						<input type="text" id="chat_ai_endpoint" name="chat_ai_endpoint" class="regular-text" value="<?php echo esc_attr( $settings['ai_endpoint'] ); ?>" />
						<p class="description"><?php esc_html_e( 'OpenAI-compatible chat-completions URL — works with OpenAI, OpenRouter, Groq and similar providers.', 'arc-starter-templates' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="chat_ai_key"><?php esc_html_e( 'API key', 'arc-starter-templates' ); ?></label></th>
					<td>
						<input type="password" id="chat_ai_key" name="chat_ai_key" class="regular-text" value="" autocomplete="new-password"
							placeholder="<?php echo '' !== trim( (string) $settings['ai_key'] ) ? esc_attr__( 'Key saved — type to replace', 'arc-starter-templates' ) : 'sk-…'; ?>" />
						<p class="description"><?php esc_html_e( 'Stored on this server and never sent to visitors. Leave empty to keep the saved key.', 'arc-starter-templates' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="chat_ai_model"><?php esc_html_e( 'Model', 'arc-starter-templates' ); ?></label></th>
					<td><input type="text" id="chat_ai_model" name="chat_ai_model" class="regular-text" value="<?php echo esc_attr( $settings['ai_model'] ); ?>" placeholder="gpt-4o-mini" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="chat_ai_context"><?php esc_html_e( 'Business context', 'arc-starter-templates' ); ?></label></th>
					<td>
						<textarea id="chat_ai_context" name="chat_ai_context" class="large-text" rows="3"><?php echo esc_textarea( $settings['ai_context'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'What the site/business is about — the AI answers questions on this topic and points everything else to the contact form.', 'arc-starter-templates' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="arc-chat-card">
			<div class="arc-chat-card__head">
				<h2><?php esc_html_e( 'Default answers', 'arc-starter-templates' ); ?></h2>
				<p><?php esc_html_e( 'Each row maps keywords (comma separated) to the reply the bot sends. Matching is case-insensitive; the row with the most keyword hits wins. These answers are also the fallback when AI answers are on but the provider fails.', 'arc-starter-templates' ); ?></p>
			</div>
			<table class="widefat striped" id="arc-chat-pairs">
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
		</div>

		<div class="arc-chat-card">
			<div class="arc-chat-card__head">
				<h2><?php esc_html_e( 'Test the bot', 'arc-starter-templates' ); ?></h2>
				<p><?php esc_html_e( 'Try a visitor message against the keyword table above — unsaved edits count. This is the reply path visitors get while the AI is off or unavailable.', 'arc-starter-templates' ); ?></p>
			</div>
			<div class="arc-chat-testbox" id="arc-chat-test-out">
				<span class="arc-test-empty"><?php esc_html_e( 'Type a message below to preview the bot reply.', 'arc-starter-templates' ); ?></span>
			</div>
			<div class="arc-chat-testrow">
				<input type="text" id="arc-chat-test-in" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. is the service available?', 'arc-starter-templates' ); ?>" />
				<button type="button" class="button button-primary" id="arc-chat-test-run"><?php esc_html_e( 'Send', 'arc-starter-templates' ); ?></button>
			</div>
		</div>

		<p class="submit"><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save chat settings', 'arc-starter-templates' ); ?></button></p>
	</form>

	<div class="arc-chat-card">
		<div class="arc-chat-card__head" style="display:flex;align-items:baseline;gap:10px">
			<h2><?php esc_html_e( 'Conversations', 'arc-starter-templates' ); ?></h2>
			<p>
				<?php echo esc_html( sprintf( /* translators: %d: conversation count. */ __( '%d sessions', 'arc-starter-templates' ), $total ) ); ?>
			</p>
		</div>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="arc-chat-toolbar">
			<input type="hidden" name="page" value="<?php echo esc_attr( Arc_ST_Chat::PAGE ); ?>" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" class="regular-text"
				placeholder="<?php esc_attr_e( 'Search session, message or email…', 'arc-starter-templates' ); ?>" />
			<label style="white-space:nowrap">
				<input type="checkbox" name="leads" value="1" <?php checked( $leads ); ?> />
				<?php esc_html_e( 'Leads only', 'arc-starter-templates' ); ?>
			</label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'arc-starter-templates' ); ?></button>
			<?php if ( '' !== $search || $leads ) : ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Arc_ST_Chat::PAGE ) ); ?>"><?php esc_html_e( 'Reset', 'arc-starter-templates' ); ?></a>
			<?php endif; ?>
			<span class="arc-toolbar-right">
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'arc_st_chat_export', 's' => $search, 'leads' => $leads ? 1 : 0 ), admin_url( 'admin-post.php' ) ), 'arc_st_chat_export' ) ); ?>">
					<?php esc_html_e( 'Export CSV', 'arc-starter-templates' ); ?>
				</a>
				<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_chat_clear' ), 'arc_st_chat_clear' ) ); ?>"
					onclick="return confirm('<?php echo esc_js( __( 'Delete ALL chat logs? This cannot be undone.', 'arc-starter-templates' ) ); ?>');">
					<?php esc_html_e( 'Clear all', 'arc-starter-templates' ); ?>
				</a>
			</span>
		</form>

		<?php if ( empty( $conversations ) ) : ?>
			<div class="arc-chat-empty">
				<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
				<?php echo '' !== $search || $leads ? esc_html__( 'No conversations match the current filters.', 'arc-starter-templates' ) : esc_html__( 'No conversations yet — they appear here once visitors use the chat on an imported page.', 'arc-starter-templates' ); ?>
			</div>
		<?php else : ?>
			<table class="widefat striped arc-chat-log">
				<thead>
					<tr>
						<th style="width:140px"><?php esc_html_e( 'Session', 'arc-starter-templates' ); ?></th>
						<th style="width:140px"><?php esc_html_e( 'Last activity', 'arc-starter-templates' ); ?></th>
						<th style="width:160px"><?php esc_html_e( 'Page', 'arc-starter-templates' ); ?></th>
						<th style="width:170px"><?php esc_html_e( 'Lead', 'arc-starter-templates' ); ?></th>
						<th><?php esc_html_e( 'Messages', 'arc-starter-templates' ); ?></th>
						<th style="width:60px"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $conversations as $c ) : ?>
					<tr>
						<td><code class="arc-session"><?php echo esc_html( substr( $c['session'], 0, 12 ) ); ?></code></td>
						<td><?php echo esc_html( $c['last'] ); ?></td>
						<td style="word-break:break-all"><?php echo esc_html( wp_parse_url( (string) $c['page'], PHP_URL_PATH ) ?: $c['page'] ); ?></td>
						<td>
							<?php if ( ! empty( $c['lead_email'] ) ) : ?>
								<a class="arc-badge arc-badge--lead" href="mailto:<?php echo esc_attr( $c['lead_email'] ); ?>">
									<span class="dashicons dashicons-email-alt" style="font-size:13px;width:13px;height:13px"></span><?php echo esc_html( $c['lead_email'] ); ?>
								</a>
							<?php else : ?>
								<span style="color:#a7aaad">—</span>
							<?php endif; ?>
						</td>
						<td>
							<details>
								<summary><?php echo esc_html( sprintf( /* translators: %d: lines. */ __( '%d messages', 'arc-starter-templates' ), (int) $c['msg_count'] ) ); ?></summary>
								<div class="arc-thread">
									<?php foreach ( $c['messages'] as $msg ) : ?>
										<div class="arc-msg <?php echo 'visitor' === $msg['role'] ? 'arc-msg--visitor' : 'arc-msg--bot'; ?>">
											<span class="arc-msg__role">
												<?php echo 'visitor' === $msg['role'] ? esc_html__( 'Visitor', 'arc-starter-templates' ) : esc_html__( 'Bot', 'arc-starter-templates' ); ?>
											</span>
											<?php echo esc_html( $msg['message'] ); ?>
											<time class="arc-msg__time"><?php echo esc_html( $msg['created'] ); ?></time>
										</div>
									<?php endforeach; ?>
								</div>
							</details>
						</td>
						<td>
							<a class="button arc-chat-del" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=arc_st_chat_delete&session=' . $c['session'] ), 'arc_st_chat_delete_' . $c['session'] ) ); ?>"
								aria-label="<?php esc_attr_e( 'Delete conversation', 'arc-starter-templates' ); ?>"
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

	// --- "Test the bot" — same accent-folding, whole-word matching the widget
	// runs on the front end, over the pairs currently in the form. -----------
	function arcNorm(s) {
		let out = String(s || '').toLowerCase();
		if (out.normalize) out = out.normalize('NFD').replace(/[̀-ͯ]/g, '');
		out = out.replace(/[^\p{L}\p{N}]+/gu, ' ').replace(/\s+/g, ' ').trim();
		return ' ' + out + ' ';
	}

	function arcAnswer(msg) {
		const nmsg = arcNorm(msg);
		let best = null, bestScore = 0, bestLen = 0, hits = [];
		document.querySelectorAll('#arc-chat-pairs tbody tr').forEach(function (tr) {
			const kw = tr.querySelector('input[name="chat_kw[]"]');
			const a = tr.querySelector('textarea[name="chat_a[]"]');
			if (!kw || !a) return;
			let score = 0, len = 0, mine = [];
			kw.value.split(',').map(function (k) { return arcNorm(k).trim(); }).filter(Boolean).forEach(function (k) {
				if (nmsg.includes(' ' + k + ' ')) { score++; len += k.length; mine.push(k); }
			});
			if (score > bestScore || (score === bestScore && score > 0 && len > bestLen)) {
				bestScore = score; bestLen = len; best = a.value; hits = mine;
			}
		});
		return { answer: best, hits: hits, score: bestScore };
	}

	const testIn = document.getElementById('arc-chat-test-in');
	const testOut = document.getElementById('arc-chat-test-out');
	const esc = function (s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

	function bubble(kind, html) {
		const d = document.createElement('div');
		d.className = 'arc-msg arc-msg--' + kind;
		d.innerHTML = '<span class="arc-msg__role">' +
			(kind === 'visitor' ? '<?php echo esc_js( __( 'Visitor', 'arc-starter-templates' ) ); ?>' : '<?php echo esc_js( __( 'Bot', 'arc-starter-templates' ) ); ?>') +
			'</span>' + html;
		return d;
	}

	function runTest() {
		const msg = testIn.value.trim();
		if (!msg) return;
		const empty = testOut.querySelector('.arc-test-empty');
		if (empty) empty.remove();
		testOut.appendChild(bubble('visitor', esc(msg)));

		const r = arcAnswer(msg);
		const fallback = document.getElementById('chat_fallback').value;
		let reply = esc(r.score ? r.answer : fallback);
		if (r.score && r.hits.length) {
			reply += '<span class="arc-msg__via"><?php echo esc_js( __( 'Matched', 'arc-starter-templates' ) ); ?>: ' +
				r.hits.map(function (h) { return '<span class="arc-chip">' + esc(h) + '</span>'; }).join('') + '</span>';
		} else {
			reply += '<span class="arc-msg__via"><?php echo esc_js( __( 'No keyword matched — fallback', 'arc-starter-templates' ) ); ?></span>';
		}
		testOut.appendChild(bubble('bot', reply));
		testOut.scrollTop = testOut.scrollHeight;
		testIn.value = '';
		testIn.focus();
	}
	document.getElementById('arc-chat-test-run').addEventListener('click', runTest);
	testIn.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runTest(); } });
})();
</script>
