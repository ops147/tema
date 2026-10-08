<?php
/**
 * WP-CLI commands — headless import for agencies/CI/provisioning.
 *
 *   wp arc-st import [--reset] [--pages=a,b] [--no-front] [--dry-run]
 *   wp arc-st reset
 *   wp arc-st status
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * `wp arc-st` command set.
 */
final class Arc_ST_CLI {

	/**
	 * Registers the commands.
	 */
	public static function hooks() {
		WP_CLI::add_command( 'arc-st import', array( __CLASS__, 'import' ) );
		WP_CLI::add_command( 'arc-st reset', array( __CLASS__, 'reset' ) );
		WP_CLI::add_command( 'arc-st status', array( __CLASS__, 'status' ) );
	}

	/**
	 * Imports the template pages, media and site setup.
	 *
	 * ## OPTIONS
	 *
	 * [--no-reset]
	 * : Keep previously imported pages/menus (import replaces them by default).
	 *
	 * [--demo=<id>]
	 * : Demo/site to import (default: all bundled templates).
	 *
	 * [--pages=<slugs>]
	 * : Comma-separated template slugs to import (default: the demo's pages).
	 *
	 * [--no-front]
	 * : Do not set the imported Home as the front page.
	 *
	 * [--dry-run]
	 * : Report what would happen without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp arc-st import --demo=blogify
	 *     wp arc-st import --demo=blogify --no-reset
	 *     wp arc-st import --pages=index,contact --no-front
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named flags.
	 */
	public static function import( $args, $assoc_args ) {
		$dry   = isset( $assoc_args['dry-run'] );
		$demo  = isset( $assoc_args['demo'] ) ? sanitize_key( $assoc_args['demo'] ) : '';
		$slugs = isset( $assoc_args['pages'] )
			? array_filter( array_map( 'sanitize_key', explode( ',', $assoc_args['pages'] ) ) )
			: ( $demo ? Arc_ST_Templates::demo_pages( $demo ) : array_keys( Arc_ST_Templates::all() ) );
		$front = ! isset( $assoc_args['no-front'] );

		// Importing a site replaces the previous import — wipe first unless
		// --no-reset was passed.
		if ( ! isset( $assoc_args['no-reset'] ) ) {
			$deleted = $dry ? 0 : Arc_ST_Importer::reset();
			WP_CLI::log( "Reset: {$deleted} previous pages removed." );
		}

		// Media.
		$files = array();
		if ( ! $dry ) {
			foreach ( $slugs as $slug ) {
				$files += Arc_ST_Media::import_for_template( $slug );
				foreach ( Arc_ST_Media::last_errors() as $file => $err ) {
					WP_CLI::warning( "Image {$file}: {$err}" );
				}
			}
		} else {
			foreach ( $slugs as $slug ) {
				$files = array_merge( $files, array_flip( Arc_ST_Templates::image_files( $slug ) ) );
			}
		}
		WP_CLI::log( sprintf( 'Media: %d images %s.', count( $files ), $dry ? 'would be imported' : 'in library' ) );

		// Create all pages before filling any, so cross-template links resolve.
		if ( ! $dry ) {
			Arc_ST_Importer::prepare( $slugs );
		}

		// Pages.
		foreach ( $slugs as $slug ) {
			$meta = Arc_ST_Templates::get( $slug );
			if ( ! $meta ) {
				WP_CLI::warning( "Unknown template: {$slug}" );
				continue;
			}
			if ( $dry ) {
				WP_CLI::log( "Page: {$meta['name']} would be created." );
				continue;
			}
			$id = Arc_ST_Importer::import( $slug );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( "{$meta['name']}: {$id->get_error_message()}" );
			} else {
				WP_CLI::log( "Page: {$meta['name']} → #{$id}" );
			}
		}

		// Setup.
		$details = Arc_ST_Importer::setup_site( $front, $dry, $demo );
		foreach ( $details as $k => $v ) {
			WP_CLI::log( sprintf( 'Setup %s: %s', $k, is_array( $v ) ? implode( ',', $v ) : ( is_bool( $v ) ? ( $v ? 'yes' : 'no' ) : $v ) ) );
		}

		WP_CLI::success( $dry ? 'Dry run complete — nothing was written.' : 'Import complete.' );
	}

	/**
	 * Deletes every page and menu this plugin created.
	 *
	 * ## OPTIONS
	 *
	 * [--demo=<id>]
	 * : Only remove this demo's pages and menus (default: everything).
	 *
	 * ## EXAMPLES
	 *
	 *     wp arc-st reset
	 *     wp arc-st reset --demo=blogify
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named flags.
	 */
	public static function reset( $args, $assoc_args ) {
		$demo    = isset( $assoc_args['demo'] ) ? sanitize_key( $assoc_args['demo'] ) : '';
		$deleted = Arc_ST_Importer::reset( $demo );
		WP_CLI::success( "Removed {$deleted} imported pages and their menus." );
	}

	/**
	 * Shows what the plugin imported (pages, media, menus, front page).
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Named flags.
	 */
	public static function status( $args, $assoc_args ) {
		$map = Arc_ST_Importer::page_map();
		WP_CLI::log( sprintf( 'Imported pages: %d', count( $map ) ) );
		foreach ( $map as $slug => $id ) {
			WP_CLI::log( sprintf( '  %-28s #%d %s', $slug, $id, get_permalink( $id ) ) );
		}
		WP_CLI::log( sprintf( 'Media attachments: %d', count( Arc_ST_Media::map() ) ) );
		WP_CLI::log( sprintf( 'Front page: %s', (int) get_option( 'page_on_front' ) ) );
		WP_CLI::log( sprintf( 'Elementor: %s', Arc_ST_Elementor::available() ? 'active (containers)' : 'not detected' ) );
		$report = Arc_ST_State::report();
		if ( ! empty( $report['time'] ) ) {
			WP_CLI::log( sprintf( 'Last import: %s (%s, %s)', $report['time'], $report['mode'], $report['status'] ) );
		}
	}
}
