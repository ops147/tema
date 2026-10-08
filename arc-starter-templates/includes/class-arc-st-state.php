<?php
/**
 * Import state, concurrency lock and the persistent "last import" report.
 *
 * The wizard runs as sequential AJAX calls; this layer makes that flow
 * crash-safe and observable:
 *  - lock: transient that prevents two concurrent imports (10 min TTL,
 *    refreshed on every step — a dead wizard's lock expires on its own).
 *  - state: what has already been done, so a refreshed/failed run can resume.
 *  - report: human-readable summary kept after the run, shown in the library.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import-run bookkeeping.
 */
final class Arc_ST_State {

	const LOCK     = 'arc_st_import_lock';
	const STATE    = 'arc_st_import_state';
	const REPORT   = 'arc_st_last_import';
	const LOCK_TTL = 600; // 10 minutes — refreshed by every wizard step.

	/* ---------------------------------------------------------------------
	 * Lock
	 * ------------------------------------------------------------------- */

	/**
	 * Acquires the import lock.
	 *
	 * @return bool False when another run already holds it.
	 */
	public static function lock_acquire() {
		if ( get_transient( self::LOCK ) ) {
			return false;
		}
		set_transient( self::LOCK, time(), self::LOCK_TTL );
		return true;
	}

	/**
	 * Extends the lock TTL (called by every wizard step).
	 */
	public static function lock_refresh() {
		if ( get_transient( self::LOCK ) ) {
			set_transient( self::LOCK, time(), self::LOCK_TTL );
		}
	}

	/**
	 * Releases the lock.
	 */
	public static function lock_release() {
		delete_transient( self::LOCK );
	}

	/**
	 * Whether an import is currently locked.
	 *
	 * @return bool
	 */
	public static function locked() {
		return (bool) get_transient( self::LOCK );
	}

	/* ---------------------------------------------------------------------
	 * Run state (resume support)
	 * ------------------------------------------------------------------- */

	/**
	 * Empty run state.
	 *
	 * @return array
	 */
	public static function state_blank() {
		return array(
			'status'      => 'idle', // idle | running | done | failed
			'step'        => '',
			'demo'        => '',
			'reset_done'  => false,
			'media_done'  => false,
			'setup_done'  => false,
			'pages_done'  => array(),
			'dry'         => false,
			'set_front'   => true,
			'errors'      => array(),
			'started'     => 0,
			'updated'     => 0,
		);
	}

	/**
	 * Current run state.
	 *
	 * @return array
	 */
	public static function state() {
		return wp_parse_args( (array) get_option( self::STATE, array() ), self::state_blank() );
	}

	/**
	 * Merges fields into the run state.
	 *
	 * @param array $patch Fields to merge.
	 */
	public static function patch( $patch ) {
		$state            = array_merge( self::state(), (array) $patch );
		$state['updated'] = time();
		update_option( self::STATE, $state, false );
	}

	/**
	 * Remaining work for a resume run: which steps/slugs are still pending.
	 *
	 * @param array $slugs All template slugs the user selected.
	 * @return array {reset:bool, media:bool, pages:string[], setup:bool}
	 */
	public static function remaining( $slugs ) {
		$state = self::state();
		return array(
			'reset' => ! $state['reset_done'],
			'media' => ! $state['media_done'],
			'pages' => array_values( array_diff( (array) $slugs, (array) $state['pages_done'] ) ),
			'setup' => ! $state['setup_done'],
		);
	}

	/* ---------------------------------------------------------------------
	 * Last-import report (persistent, shown in the library)
	 * ------------------------------------------------------------------- */

	/**
	 * Starts a fresh report.
	 *
	 * @param string $mode elementor | classic | dry.
	 */
	public static function report_start( $mode ) {
		update_option(
			self::REPORT,
			array(
				'time'      => current_time( 'mysql' ),
				'status'    => 'running',
				'mode'      => $mode,
				'pages'     => array(),
				'images'    => array( 'imported' => 0, 'errors' => array() ),
				'setup'     => array(),
				'errors'    => array(),
			),
			false
		);
	}

	/**
	 * Merges data into the report.
	 *
	 * @param array $patch Fields to merge.
	 */
	public static function report_patch( $patch ) {
		$report = (array) get_option( self::REPORT, array() );
		update_option( self::REPORT, array_merge( $report, (array) $patch ), false );
	}

	/**
	 * Appends one entry to a report list field.
	 *
	 * @param string $field 'pages' | 'errors' | 'setup'.
	 * @param mixed  $entry Entry to append.
	 */
	public static function report_add( $field, $entry ) {
		$report = (array) get_option( self::REPORT, array() );
		if ( ! isset( $report[ $field ] ) || ! is_array( $report[ $field ] ) ) {
			$report[ $field ] = array();
		}
		$report[ $field ][] = $entry;
		update_option( self::REPORT, $report, false );
	}

	/**
	 * Current report (or empty array).
	 *
	 * @return array
	 */
	public static function report() {
		return (array) get_option( self::REPORT, array() );
	}
}
