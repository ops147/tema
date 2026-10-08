<?php
/**
 * Floating chat bot — a small assistant rendered by every imported template
 * page (and the plugin previews). Answers come from an OpenAI-compatible
 * chat API when one is configured (natural, human-sounding replies scoped to
 * the business topic) and always fall back to an editable set of
 * keyword => reply pairs — including when the provider is unreachable or out
 * of credits. Every exchange is logged to a dedicated table so the admin can
 * monitor conversations from the "Chat Bot" screen under the plugin menu.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chat settings, REST API and admin screen.
 */
final class Arc_ST_Chat {

	const TABLE   = 'arc_st_chat_logs';
	const DBV     = 'arc_st_chat_db';
	const OPTION  = 'arc_st_chat';
	const PAGE    = 'arc-st-chat';
	const AI_DOWN = 'arc_st_chat_ai_down';

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_schedule_prune' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
		add_action( 'admin_post_arc_st_chat_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_arc_st_chat_clear', array( __CLASS__, 'clear' ) );
		add_action( 'admin_post_arc_st_chat_delete', array( __CLASS__, 'delete_conversation' ) );
		add_action( 'admin_post_arc_st_chat_export', array( __CLASS__, 'export' ) );
		add_action( 'arc_st_chat_prune', array( __CLASS__, 'prune' ) );
	}

	/**
	 * Prefixed table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Creates/upgrades the log table (flagged by an option so the check is
	 * cheap — bump the compared version when the schema changes).
	 */
	public static function maybe_create_table() {
		if ( '2' === get_option( self::DBV ) ) {
			return;
		}
		self::create_table();
	}

	/**
	 * dbDelta schema — safe to run repeatedly; v2 adds the lead-capture
	 * `email` column.
	 */
	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = 'CREATE TABLE ' . self::table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session varchar(64) NOT NULL DEFAULT '',
			role varchar(10) NOT NULL DEFAULT 'visitor',
			message text NULL,
			email varchar(255) NOT NULL DEFAULT '',
			page varchar(255) NOT NULL DEFAULT '',
			created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY session (session),
			KEY created (created)
		) " . $wpdb->get_charset_collate() . ';';
		dbDelta( $sql );
		update_option( self::DBV, '2', false );
	}

	/**
	 * Built-in bot settings — the admin edits these on the Chat Bot screen.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'   => 1,
			'retention' => 0, // Days to keep chat logs; 0 = keep forever.
			'title'     => 'Ash River Collective',
			'greeting' => __( 'Hi! I\'m the ARC assistant. Ask me about our services, roles, process or pricing — or type "human" and we\'ll follow up by email.', 'arc-starter-templates' ),
			'fallback' => __( 'I can help with services, roles, process, pricing and timelines. For anything else, leave your email or use the contact form and the team will reply within one business day.', 'arc-starter-templates' ),
			'pairs'    => array(
				array(
					'kw' => __( 'services, service, what do you do, offer, servicios, servicio', 'arc-starter-templates' ),
					'a'  => __( 'ARC places trained Finance & Accounting talent and Virtual Assistants, and documents your processes into playbooks. Tell us what you need and we scope the role.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'accountant, accounting, bookkeeper, controller, finance, cfo', 'arc-starter-templates' ),
					'a'  => __( 'We place General Accountants, AR/AP Specialists, Senior Accountants, Accounting Managers, Controllers and Fractional Controllers — fluent English, U.S. hours, trained on your systems.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'virtual assistant, va, assistant, admin', 'arc-starter-templates' ),
					'a'  => __( 'ARC Virtual Assistants handle inbox, calendar, data entry, invoicing, CRM updates and reporting — with documented processes behind them, not improvisation.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'price, pricing, cost, rate, how much, precio, precios, costo, cuanto', 'arc-starter-templates' ),
					'a'  => __( 'Engagements are scoped to the work and priced for the role — dedicated, part-time or project-based. Use the contact form for a same-day quote.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'process, how it works, onboarding, start, timeline', 'arc-starter-templates' ),
					'a'  => __( 'Our model is Diagnose → Install → Execute: we map the work, document the playbook, then place talent that runs it. Most roles start within 7 days.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'contact, email, phone, human, talk, call, contacto, correo, humano', 'arc-starter-templates' ),
					'a'  => __( 'You can reach the team through the contact page or leave your work email here — a specialist replies within one business day.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'foundation, nonprofit, training', 'arc-starter-templates' ),
					'a'  => __( 'The Ash River Foundation trains and connects talent in underserved communities — because opportunity shouldn\'t depend on geography.', 'arc-starter-templates' ),
				),
				array(
					'kw' => __( 'hi, hello, hey, hola', 'arc-starter-templates' ),
					'a'  => __( 'Hello! Ask me about roles, pricing, timelines or how ARC works — or type "human" to reach the team.', 'arc-starter-templates' ),
				),
			),
			'ai_enabled'  => 0,
			'ai_endpoint' => 'https://api.openai.com/v1/chat/completions', // Any OpenAI-compatible chat-completions URL (OpenAI, OpenRouter, Groq, …).
			'ai_key'      => '',
			'ai_model'    => 'gpt-4o-mini',
			'ai_context'  => __(
				'Ash River Collective (ARC) places trained Finance & Accounting talent and Virtual Assistants with U.S. businesses, and documents client processes into step-by-step playbooks. The Ash River Foundation trains and connects talent in underserved communities.',
				'arc-starter-templates'
			),
		);
	}

	/**
	 * Current settings — option merged over the defaults so new keys always
	 * exist.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = (array) get_option( self::OPTION, array() );
		$out   = array_merge( self::defaults(), $saved );
		if ( empty( $out['pairs'] ) || ! is_array( $out['pairs'] ) ) {
			$out['pairs'] = self::defaults()['pairs'];
		}
		return $out;
	}

	/**
	 * REST namespace routes — public: the widget reads config and posts log
	 * lines for visitors who aren't logged in.
	 */
	public static function rest_routes() {
		register_rest_route(
			'arc-st/v1',
			'/chat/config',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					$s = self::settings();
					return rest_ensure_response(
						array(
							'enabled'  => (int) $s['enabled'],
							'title'    => (string) $s['title'],
							'greeting' => (string) $s['greeting'],
							'fallback' => (string) $s['fallback'],
							'pairs'    => array_values( (array) $s['pairs'] ),
							'labels'   => array(
								'placeholder' => __( 'Type your message…', 'arc-starter-templates' ),
								'send'        => __( 'Send', 'arc-starter-templates' ),
								'open'        => __( 'Open chat', 'arc-starter-templates' ),
								'close'       => __( 'Close', 'arc-starter-templates' ),
								'suggestions' => __( 'You can ask about:', 'arc-starter-templates' ),
							),
						)
					);
				},
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'arc-st/v1',
			'/chat/log',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_log' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'session' => array( 'required' => true ),
					'role'    => array( 'required' => true ),
					'message' => array( 'required' => true ),
					'page'    => array( 'required' => false ),
				),
			)
		);

		register_rest_route(
			'arc-st/v1',
			'/chat/ask',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_ask' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'message' => array( 'required' => true ),
					'session' => array( 'required' => false ),
					'page'    => array( 'required' => false ),
					'history' => array( 'required' => false ),
				),
			)
		);
	}

	/**
	 * Stores one chat line from the widget.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function rest_log( $req ) {
		global $wpdb;
		self::maybe_create_table();

		$session = sanitize_key( substr( (string) $req->get_param( 'session' ), 0, 64 ) );
		$role    = in_array( $req->get_param( 'role' ), array( 'visitor', 'bot' ), true ) ? $req->get_param( 'role' ) : 'visitor';
		$message = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'message' ) ), 0, 2000 );
		$page    = mb_substr( esc_url_raw( (string) $req->get_param( 'page' ) ), 0, 255 );

		// Lead capture: a visitor message carrying an email address stores it
		// so the admin screen can surface the session as a lead to follow up.
		$email = '';
		if ( 'visitor' === $role && preg_match( '/[\w.+-]+@[\w-]+\.[\w.]{2,}/', $message, $m ) ) {
			$email = (string) sanitize_email( $m[0] );
		}

		if ( '' === $session || '' === $message ) {
			return rest_ensure_response( array( 'ok' => false ) );
		}

		// Rate limit per IP: the endpoint is public, so the per-session cap
		// below alone could be bypassed by rotating session ids.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' !== $ip ) {
			$rl_key = 'arc_st_chat_rl_' . md5( $ip );
			$hits   = (int) get_transient( $rl_key );
			$limit  = (int) apply_filters( 'arc_st_chat_rate_limit', 30 ); // Logged lines per minute per IP.
			if ( $hits >= $limit ) {
				return rest_ensure_response( array( 'ok' => false, 'reason' => 'rate_limited' ) );
			}
			set_transient( $rl_key, $hits + 1, MINUTE_IN_SECONDS );
		}

		// Cheap flood control: cap a session at 500 logged lines.
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE session = %s', $session ) );
		if ( $count >= 500 ) {
			return rest_ensure_response( array( 'ok' => false, 'reason' => 'limit' ) );
		}

		$wpdb->insert(
			self::table(),
			array(
				'session' => $session,
				'role'    => $role,
				'message' => $message,
				'email'   => $email,
				'page'    => $page,
				'created' => current_time( 'mysql' ),
			)
		);

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Answers one visitor message — the AI provider when configured, the
	 * keyword table otherwise. Always returns a reply: if the provider is
	 * down, out of credits or times out, visitors still get the default
	 * answer that best fits what they asked.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function rest_ask( $req ) {
		$message = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'message' ) ), 0, 1000 );
		if ( '' === trim( $message ) ) {
			return rest_ensure_response( array( 'ok' => false ) );
		}

		// Rate limit per IP — AI calls cost money, so stricter than /chat/log.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' !== $ip ) {
			$rl_key = 'arc_st_chat_ask_rl_' . md5( $ip );
			$hits   = (int) get_transient( $rl_key );
			$limit  = (int) apply_filters( 'arc_st_chat_ask_rate_limit', 20 ); // Replies per minute per IP.
			if ( $hits >= $limit ) {
				return rest_ensure_response( array( 'ok' => true, 'source' => 'default', 'reply' => self::local_answer( $message ) ) );
			}
			set_transient( $rl_key, $hits + 1, MINUTE_IN_SECONDS );
		}

		$s = self::settings();
		if ( ! empty( $s['ai_enabled'] ) && '' !== trim( (string) $s['ai_key'] ) && ! get_transient( self::AI_DOWN ) ) {
			$reply = self::ai_answer( $message, $req->get_param( 'history' ) );
			if ( null !== $reply ) {
				return rest_ensure_response( array( 'ok' => true, 'source' => 'ai', 'reply' => $reply ) );
			}
		}

		return rest_ensure_response( array( 'ok' => true, 'source' => 'default', 'reply' => self::local_answer( $message ) ) );
	}

	/**
	 * Best default answer for a message — the same whole-word, accent-folded
	 * scoring the widget runs client-side, so the fallback stays adapted to
	 * what the visitor is asking about.
	 *
	 * @param string $text Visitor message.
	 * @return string
	 */
	public static function local_answer( $text ) {
		$s          = self::settings();
		$msg        = self::normalize( $text );
		$best       = null;
		$best_score = 0;
		$best_len   = 0;
		foreach ( (array) $s['pairs'] as $p ) {
			$score = 0;
			$len   = 0;
			foreach ( explode( ',', (string) ( isset( $p['kw'] ) ? $p['kw'] : '' ) ) as $k ) {
				$k = trim( self::normalize( $k ) );
				if ( '' !== $k && false !== strpos( $msg, ' ' . $k . ' ' ) ) {
					$score++;
					$len += strlen( $k );
				}
			}
			// Most keyword hits wins; equal hits → the pair with the longest
			// total match is the more specific answer.
			if ( $score > $best_score || ( $score === $best_score && $score > 0 && $len > $best_len ) ) {
				$best_score = $score;
				$best_len   = $len;
				$best       = (string) $p['a'];
			}
		}
		return null !== $best ? $best : (string) $s['fallback'];
	}

	/**
	 * Accent-folding word normalizer — "Cotización — precios!" becomes
	 * " cotizacion precios " so keyword checks match whole words/phrases,
	 * never substrings inside other words.
	 *
	 * @param string $s Raw text.
	 * @return string
	 */
	public static function normalize( $s ) {
		$s = remove_accents( mb_strtolower( (string) $s ) );
		$s = (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $s );
		$s = trim( (string) preg_replace( '/\s+/', ' ', $s ) );
		return ' ' . $s . ' ';
	}

	/**
	 * Calls the configured OpenAI-compatible chat API. Returns the reply text
	 * or null on any failure — failures are cached in a short-lived transient
	 * (circuit breaker) so an exhausted key or outage doesn't add the API
	 * timeout to every message.
	 *
	 * @param string $message Visitor message.
	 * @param mixed  $history Recent turns sent by the widget ([{role,content}]).
	 * @return string|null
	 */
	private static function ai_answer( $message, $history ) {
		$s         = self::settings();
		$messages  = array(
			array(
				'role'    => 'system',
				'content' => self::ai_prompt(),
			),
		);
		$last_role = 'system';

		if ( is_array( $history ) ) {
			foreach ( array_slice( $history, -8 ) as $h ) {
				if ( ! is_array( $h ) || empty( $h['content'] ) ) {
					continue;
				}
				$role    = isset( $h['role'] ) && 'user' === $h['role'] ? 'user' : 'assistant';
				$content = mb_substr( sanitize_textarea_field( (string) $h['content'] ), 0, 1000 );
				// Strictly alternating turns — some providers reject repeats.
				if ( '' !== trim( $content ) && $role !== $last_role ) {
					$messages[] = array( 'role' => $role, 'content' => $content );
					$last_role  = $role;
				}
			}
		}
		if ( 'user' === $last_role ) {
			array_pop( $messages ); // The new user turn replaces a trailing one.
		}
		$messages[] = array( 'role' => 'user', 'content' => $message );

		$endpoint = trim( (string) $s['ai_endpoint'] );
		if ( '' === $endpoint ) {
			$endpoint = 'https://api.openai.com/v1/chat/completions';
		}
		$model = trim( (string) $s['ai_model'] );
		if ( '' === $model ) {
			$model = 'gpt-4o-mini';
		}

		$res = wp_remote_post(
			$endpoint,
			array(
				'timeout' => (int) apply_filters( 'arc_st_chat_ai_timeout', 20 ),
				'headers' => array(
					'Authorization' => 'Bearer ' . trim( (string) $s['ai_key'] ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'messages'    => $messages,
						'max_tokens'  => 220,
						'temperature' => 0.7,
					)
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			self::ai_down( $res->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$text = isset( $data['choices'][0]['message']['content'] ) ? trim( (string) $data['choices'][0]['message']['content'] ) : '';

		if ( 200 !== $code || '' === $text ) {
			$reason = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : 'HTTP ' . $code;
			self::ai_down( $reason );
			return null;
		}

		return $text;
	}

	/**
	 * System prompt for the AI — human tone, scoped to the business topic,
	 * with the configured keyword answers embedded as approved facts so both
	 * answer paths stay consistent.
	 *
	 * @return string
	 */
	private static function ai_prompt() {
		$s     = self::settings();
		$facts = array();
		foreach ( (array) $s['pairs'] as $p ) {
			if ( ! empty( $p['a'] ) ) {
				$facts[] = '- ' . trim( (string) $p['a'] );
			}
		}

		$prompt = sprintf(
			/* translators: %s: chat/business title. */
			'You are the website assistant for "%s" — you sound like a real person on the team: warm, natural, conversational, never robotic or scripted. Keep replies short (1–3 sentences, under 60 words), answer in the same language the visitor writes in, and avoid lists and emojis unless the visitor uses them. Only discuss what the business offers, using the context and facts below. If a question is unrelated or you are unsure, say so honestly and suggest the contact form or leaving a work email. Never invent prices, dates or policies.',
			trim( (string) $s['title'] )
		);

		$context = trim( (string) $s['ai_context'] );
		if ( '' !== $context ) {
			$prompt .= "\n\nAbout the business: " . $context;
		}
		if ( $facts ) {
			$prompt .= "\n\nApproved facts:\n" . implode( "\n", $facts );
		}
		return (string) apply_filters( 'arc_st_chat_ai_prompt', $prompt );
	}

	/**
	 * Marks the AI provider as temporarily unavailable; /chat/ask skips the
	 * API call while the transient lives and the admin screen shows a notice.
	 *
	 * @param string $reason Provider error message (trimmed, never the key).
	 */
	private static function ai_down( $reason ) {
		set_transient(
			self::AI_DOWN,
			array(
				'reason' => mb_substr( $reason, 0, 200 ),
				'since'  => current_time( 'mysql' ),
			),
			(int) apply_filters( 'arc_st_chat_ai_retry_after', 15 * MINUTE_IN_SECONDS )
		);
	}

	/**
	 * "Chat Bot" submenu under the plugin menu.
	 */
	public static function menu() {
		add_submenu_page(
			Arc_ST_Admin::PAGE_LIBRARY,
			__( 'Chat Bot', 'arc-starter-templates' ),
			__( 'Chat Bot', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Renders settings + Q&A editor + conversation monitor.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied' );
		}
		$settings = self::settings();
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$leads    = ! empty( $_GET['leads'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$stats    = self::stats();
		$ai_down  = get_transient( self::AI_DOWN );
		require ARC_ST_PATH . 'admin/partials/chat.php';
	}

	/**
	 * Session ids matching the admin filters — free-text search over
	 * session id, message body and captured email, and/or leads-only.
	 *
	 * @param string $search     Free-text search ('' = off).
	 * @param bool   $leads_only Restrict to sessions that left an email.
	 * @return array<int,string>|null Null when unfiltered.
	 */
	public static function matching_sessions( $search = '', $leads_only = false ) {
		if ( '' === $search && ! $leads_only ) {
			return null;
		}
		global $wpdb;
		$where = array( '1=1' );
		$args  = array();
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(session LIKE %s OR message LIKE %s OR email LIKE %s)';
			$args[]  = $like;
			$args[]  = $like;
			$args[]  = $like;
		}
		if ( $leads_only ) {
			$where[] = "email <> ''";
		}
		$sql = 'SELECT DISTINCT session FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $args ? (array) $wpdb->get_col( $wpdb->prepare( $sql, $args ) ) : (array) $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Latest conversations (one row per session, with its messages).
	 *
	 * @param int    $paged      Page number.
	 * @param int    $per        Sessions per page.
	 * @param string $search     Free-text filter ('' = off).
	 * @param bool   $leads_only Restrict to sessions with a captured email.
	 * @return array
	 */
	public static function conversations( $paged = 1, $per = 20, $search = '', $leads_only = false ) {
		global $wpdb;
		$match = self::matching_sessions( $search, $leads_only );
		if ( is_array( $match ) && ! $match ) {
			return array(); // Filtered and nothing matched.
		}

		$tail = array( $per, max( 0, (int) $paged - 1 ) * $per );
		if ( null === $match ) {
			$sql  = 'SELECT session, MAX(created) AS last, COUNT(*) AS msg_count, MAX(page) AS page, MAX(email) AS lead_email
				FROM ' . self::table() . ' GROUP BY session ORDER BY last DESC LIMIT %d OFFSET %d';
			$args = $tail;
		} else {
			$ph   = implode( ',', array_fill( 0, count( $match ), '%s' ) );
			$sql  = 'SELECT session, MAX(created) AS last, COUNT(*) AS msg_count, MAX(page) AS page, MAX(email) AS lead_email
				FROM ' . self::table() . ' WHERE session IN (' . $ph . ') GROUP BY session ORDER BY last DESC LIMIT %d OFFSET %d';
			$args = array_merge( $match, $tail );
		}
		$sessions = (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $sessions as &$s ) {
			$s['messages'] = (array) $wpdb->get_results(
				$wpdb->prepare(
					'SELECT role, message, created FROM ' . self::table() . ' WHERE session = %s ORDER BY id ASC',
					$s['session']
				),
				ARRAY_A
			);
		}
		return $sessions;
	}

	/**
	 * Distinct session count (for pagination), honoring the same filters.
	 *
	 * @param string $search     Free-text filter ('' = off).
	 * @param bool   $leads_only Restrict to sessions with a captured email.
	 * @return int
	 */
	public static function conversation_count( $search = '', $leads_only = false ) {
		global $wpdb;
		$match = self::matching_sessions( $search, $leads_only );
		if ( is_array( $match ) ) {
			return count( $match );
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT session) FROM ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Headline numbers for the admin screen.
	 *
	 * @return array{sessions:int,messages:int,leads:int,today:int}
	 */
	public static function stats() {
		global $wpdb;
		$t = self::table();
		return array(
			'sessions' => (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT session) FROM ' . $t ), // phpcs:ignore WordPress.DB.PreparedSQL
			'messages' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $t ), // phpcs:ignore WordPress.DB.PreparedSQL
			'leads'    => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session) FROM " . $t . " WHERE email <> ''" ), // phpcs:ignore WordPress.DB.PreparedSQL
			'today'    => (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $t . ' WHERE created >= %s', // phpcs:ignore WordPress.DB.PreparedSQL
					gmdate( 'Y-m-d 00:00:00', (int) current_time( 'timestamp' ) )
				)
			),
		);
	}

	/**
	 * Keeps the daily prune event in sync with the retention setting.
	 */
	public static function maybe_schedule_prune() {
		$s          = self::settings();
		$days       = isset( $s['retention'] ) ? (int) $s['retention'] : 0;
		$is_sched   = (bool) wp_next_scheduled( 'arc_st_chat_prune' );
		if ( $days > 0 && ! $is_sched ) {
			wp_schedule_event( time(), 'daily', 'arc_st_chat_prune' );
		} elseif ( 0 === $days && $is_sched ) {
			wp_clear_scheduled_hook( 'arc_st_chat_prune' );
		}
	}

	/**
	 * Daily cron: drops log rows older than the configured retention.
	 */
	public static function prune() {
		$s    = self::settings();
		$days = isset( $s['retention'] ) ? (int) $s['retention'] : 0;
		if ( $days <= 0 ) {
			return;
		}
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table() . ' WHERE created < %s', // phpcs:ignore WordPress.DB.PreparedSQL
				gmdate( 'Y-m-d H:i:s', (int) current_time( 'timestamp' ) - $days * DAY_IN_SECONDS )
			)
		);
	}

	/**
	 * Streams the filtered log as CSV (same filters as the monitor).
	 */
	public static function export() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_export' ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$leads  = ! empty( $_GET['leads'] );
		$match  = self::matching_sessions( $search, $leads );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=arc-st-chat-' . gmdate( 'Ymd-His' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'session', 'created', 'role', 'message', 'page', 'email' ) );
		if ( null === $match || $match ) {
			$sql  = 'SELECT session, created, role, message, page, email FROM ' . self::table();
			$args = array();
			if ( is_array( $match ) ) {
				$sql .= ' WHERE session IN (' . implode( ',', array_fill( 0, count( $match ), '%s' ) ) . ')';
				$args = $match;
			}
			$sql .= ' ORDER BY session, id';
			$rows  = $args
				? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL
				: $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, $r );
			}
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Saves the settings form (title, greeting, fallback, AI, Q&A pairs).
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_save' ) ) {
			wp_die( 'Denied' );
		}
		$pairs = array();
		$kws   = isset( $_POST['chat_kw'] ) ? (array) wp_unslash( $_POST['chat_kw'] ) : array();
		$ans   = isset( $_POST['chat_a'] ) ? (array) wp_unslash( $_POST['chat_a'] ) : array();
		foreach ( $kws as $i => $kw ) {
			$kw = sanitize_text_field( (string) $kw );
			$a  = isset( $ans[ $i ] ) ? sanitize_textarea_field( (string) $ans[ $i ] ) : '';
			if ( '' !== $kw && '' !== $a ) {
				$pairs[] = array( 'kw' => $kw, 'a' => $a );
			}
		}

		// The API key field submits empty to keep the stored key — the input
		// never echoes the secret back into the page.
		$posted_key = isset( $_POST['chat_ai_key'] ) ? sanitize_text_field( wp_unslash( $_POST['chat_ai_key'] ) ) : '';
		$current    = self::settings();

		update_option(
			self::OPTION,
			array(
				'enabled'     => isset( $_POST['chat_enabled'] ) ? 1 : 0,
				'retention'   => isset( $_POST['chat_retention'] ) ? min( 365, absint( $_POST['chat_retention'] ) ) : 0, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'title'       => sanitize_text_field( wp_unslash( isset( $_POST['chat_title'] ) ? $_POST['chat_title'] : '' ) ),
				'greeting'    => sanitize_textarea_field( wp_unslash( isset( $_POST['chat_greeting'] ) ? $_POST['chat_greeting'] : '' ) ),
				'fallback'    => sanitize_textarea_field( wp_unslash( isset( $_POST['chat_fallback'] ) ? $_POST['chat_fallback'] : '' ) ),
				'ai_enabled'  => isset( $_POST['chat_ai_enabled'] ) ? 1 : 0,
				'ai_endpoint' => esc_url_raw( wp_unslash( isset( $_POST['chat_ai_endpoint'] ) ? $_POST['chat_ai_endpoint'] : '' ) ),
				'ai_key'      => '' !== $posted_key ? $posted_key : (string) $current['ai_key'],
				'ai_model'    => sanitize_text_field( wp_unslash( isset( $_POST['chat_ai_model'] ) ? $_POST['chat_ai_model'] : '' ) ),
				'ai_context'  => sanitize_textarea_field( wp_unslash( isset( $_POST['chat_ai_context'] ) ? $_POST['chat_ai_context'] : '' ) ),
				'pairs'       => $pairs,
			),
			false
		);
		delete_transient( self::AI_DOWN ); // Fresh settings get an immediate retry.
		self::maybe_schedule_prune();
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_saved=1' ) );
		exit;
	}

	/**
	 * Deletes the whole log.
	 */
	public static function clear() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_clear' ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_cleared=1' ) );
		exit;
	}

	/**
	 * Deletes one conversation (all lines of a session).
	 */
	public static function delete_conversation() {
		$session = isset( $_GET['session'] ) ? sanitize_key( wp_unslash( $_GET['session'] ) ) : '';
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_chat_delete_' . $session ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'session' => $session ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_chat_deleted=1' ) );
		exit;
	}
}
