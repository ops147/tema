<?php
/**
 * Form entries — stores every submission from the imported templates'
 * forms (contact + newsletter) in a dedicated table and exposes them on a
 * "Form Entries" admin screen inside the plugin menu.
 *
 * The table is created on plugin activation AND lazily (admin_init + first
 * insert) so installs where the plugin was already active still get it.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Submissions storage + admin list screen.
 */
final class Arc_ST_Entries {

	const TABLE = 'arc_st_entries';
	const DBV   = 'arc_st_entries_db';
	const PAGE  = 'arc-st-entries';

	/**
	 * Registers hooks.
	 */
	public static function hooks() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_table' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_arc_st_entry_delete', array( __CLASS__, 'delete_entry' ) );
		add_action( 'admin_post_arc_st_entries_clear', array( __CLASS__, 'clear' ) );
		add_action( 'admin_post_arc_st_entries_csv', array( __CLASS__, 'export_csv' ) );
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
	 * Creates the table once (flagged by an option so the check is cheap).
	 */
	public static function maybe_create_table() {
		if ( '1' === get_option( self::DBV ) ) {
			return;
		}
		self::create_table();
	}

	/**
	 * dbDelta schema — safe to run repeatedly.
	 */
	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = 'CREATE TABLE ' . self::table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form varchar(32) NOT NULL DEFAULT 'contact',
			name varchar(190) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			data longtext NULL,
			page varchar(255) NOT NULL DEFAULT '',
			created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY form (form),
			KEY created (created)
		) " . $wpdb->get_charset_collate() . ';';
		dbDelta( $sql );
		update_option( self::DBV, '1', false );
	}

	/**
	 * Stores one submission. Called by the contact and subscribe handlers —
	 * entries must persist even when wp_mail fails.
	 *
	 * @param string $form  Form kind (contact | subscribe).
	 * @param string $name  Sender display name ('' for newsletter).
	 * @param string $email Sender email.
	 * @param array  $data  Full sanitized field payload.
	 * @param string $page  Page URL the form was submitted from.
	 * @return int Entry ID.
	 */
	public static function insert( $form, $name, $email, $data = array(), $page = '' ) {
		global $wpdb;
		self::maybe_create_table();
		$wpdb->insert(
			self::table(),
			array(
				'form'    => sanitize_key( $form ),
				'name'    => mb_substr( (string) $name, 0, 190 ),
				'email'   => mb_substr( (string) $email, 0, 190 ),
				'data'    => wp_json_encode( $data ),
				'page'    => mb_substr( esc_url_raw( (string) $page ), 0, 255 ),
				'created' => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Total entries, optionally filtered by form kind.
	 *
	 * @param string $form 'contact' | 'subscribe' | '' (all).
	 * @return int
	 */
	public static function count( $form = '' ) {
		global $wpdb;
		$where = '' !== $form ? $wpdb->prepare( 'WHERE form = %s', $form ) : '';
		// phpcs:ignore WordPress.DB.PreparedSQL -- $where is prepared above.
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . " {$where}" );
	}

	/**
	 * Page of entries, newest first.
	 *
	 * @param int    $paged Page number (1-based).
	 * @param int    $per   Rows per page.
	 * @param string $form  Optional form-kind filter.
	 * @return array Rows.
	 */
	public static function list_entries( $paged = 1, $per = 20, $form = '' ) {
		global $wpdb;
		$where = '' !== $form ? $wpdb->prepare( 'WHERE form = %s', $form ) : '';
		// phpcs:ignore WordPress.DB.PreparedSQL -- $where prepared, limits are ints.
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . " {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per,
				max( 0, (int) $paged - 1 ) * $per
			),
			ARRAY_A
		);
	}

	/**
	 * "Form Entries" submenu under the plugin menu.
	 */
	public static function menu() {
		add_submenu_page(
			Arc_ST_Admin::PAGE_LIBRARY,
			__( 'Form Entries', 'arc-starter-templates' ),
			__( 'Form Entries', 'arc-starter-templates' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Renders the entries screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied' );
		}
		$form   = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per    = 20;
		$total  = self::count( $form );
		$rows   = self::list_entries( $paged, $per, $form );
		$pages  = max( 1, (int) ceil( $total / $per ) );
		$labels = self::field_labels();
		require ARC_ST_PATH . 'admin/partials/entries.php';
	}

	/**
	 * Human labels for the stored field keys (shared with the CSV export).
	 *
	 * @return array
	 */
	public static function field_labels() {
		return array(
			'first-name' => __( 'First Name', 'arc-starter-templates' ),
			'last-name'  => __( 'Last Name', 'arc-starter-templates' ),
			'company'    => __( 'Company', 'arc-starter-templates' ),
			'phone'      => __( 'Phone', 'arc-starter-templates' ),
			'work-email' => __( 'Email', 'arc-starter-templates' ),
			'email'      => __( 'Email', 'arc-starter-templates' ),
			'need'       => __( 'Topic', 'arc-starter-templates' ),
			'count'      => __( 'Quantity', 'arc-starter-templates' ),
			'when'       => __( 'Timeline', 'arc-starter-templates' ),
			'details'    => __( 'Details', 'arc-starter-templates' ),
		);
	}

	/**
	 * Deletes a single entry.
	 */
	public static function delete_entry() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_entry_delete_' . $id ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => $id ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_entry_deleted=1' ) );
		exit;
	}

	/**
	 * Deletes every entry.
	 */
	public static function clear() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_entries_clear' ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&arc_entries_cleared=1' ) );
		exit;
	}

	/**
	 * Streams all entries as CSV (name,email,form,page,date + field columns).
	 */
	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_st_entries_csv' ) ) {
			wp_die( 'Denied' );
		}
		global $wpdb;
		$rows = (array) $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id ASC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=arc-form-entries-' . gmdate( 'Ymd-His' ) . '.csv' );

		$labels = self::field_labels();
		$out    = fopen( 'php://output', 'w' );
		fputcsv( $out, array_merge( array( 'id', 'form', 'name', 'email', 'page', 'created' ), array_values( $labels ) ) );
		foreach ( $rows as $row ) {
			$data = (array) json_decode( (string) $row['data'], true );
			// Los campos son texto libre del visitante: prefijar con ' evita
			// que =, +, -, @ se interpreten como fórmula al abrir el CSV.
			$line = array_map( array( __CLASS__, 'csv_safe' ), array( $row['id'], $row['form'], $row['name'], $row['email'], $row['page'], $row['created'] ) );
			foreach ( $labels as $key => $label ) {
				$line[] = self::csv_safe( isset( $data[ $key ] ) ? $data[ $key ] : '' );
			}
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Neutraliza formula injection en celdas de spreadsheet (CSV/Excel).
	 *
	 * @param mixed $value Cell value.
	 * @return mixed
	 */
	private static function csv_safe( $value ) {
		if ( is_string( $value ) && '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
