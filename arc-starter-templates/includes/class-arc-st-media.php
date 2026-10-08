<?php
/**
 * Media handling — uploads bundled template images into the Media Library so
 * imported pages keep working if the plugin is later removed.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sideloads plugin assets/img/* files as attachments (idempotent).
 */
final class Arc_ST_Media {

	const OPTION = 'arc_st_media_map';

	/**
	 * Errors collected by the last import_for_template() call.
	 *
	 * @var array filename => message
	 */
	private static $last_errors = array();

	/**
	 * filename => attachment ID map.
	 *
	 * @return array
	 */
	public static function map() {
		return (array) get_option( self::OPTION, array() );
	}

	/**
	 * Imports one bundled image. Returns cached result on repeat calls.
	 *
	 * @param string $filename Basename inside assets/img/.
	 * @param string $alt      Alt text to store on first import.
	 * @return array|WP_Error array{id:int,url:string} on success.
	 */
	public static function import( $filename, $alt = '' ) {
		$filename = basename( (string) $filename );
		$map      = self::map();

		if ( isset( $map[ $filename ] ) && get_post( $map[ $filename ] ) ) {
			return array(
				'id'  => (int) $map[ $filename ],
				'url' => (string) wp_get_attachment_url( $map[ $filename ] ),
			);
		}

		// Source order: theme override → remote repo → bundled copy. Theme
		// overrides may reference their own images — the same locate_template()
		// convention as the template files applies:
		// <theme>/arc-starter-templates/assets/img/<file> (or /img/<file>).
		$src = '';
		if ( function_exists( 'locate_template' ) ) {
			foreach ( array( 'arc-starter-templates/assets/img/', 'arc-starter-templates/img/' ) as $dir ) {
				$theme_src = locate_template( $dir . $filename );
				if ( '' !== $theme_src && is_readable( $theme_src ) ) {
					$src = $theme_src;
					break;
				}
			}
		}
		if ( '' === $src && Arc_ST_Remote::enabled() ) {
			$remote = Arc_ST_Remote::fetch( 'assets/img/' . $filename );
			if ( '' !== $remote ) {
				$src = $remote;
			}
		}
		if ( '' === $src ) {
			$src = ARC_ST_PATH . 'assets/img/' . $filename;
		}
		if ( ! is_readable( $src ) ) {
			return new WP_Error( 'arc_st_missing_image', "Missing image: {$filename}" );
		}

		$upload = wp_upload_bits( $filename, null, (string) file_get_contents( $src ) );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'arc_st_upload', (string) $upload['error'] );
		}

		$type      = wp_check_filetype( $upload['file'] );
		$title     = trim( preg_replace( '/[-_]+/', ' ', (string) pathinfo( $filename, PATHINFO_FILENAME ) ) );
		$attach_id = wp_insert_attachment(
			array(
				'post_mime_type' => $type['type'] ? $type['type'] : 'image/jpeg',
				'post_title'     => $title,
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			return new WP_Error( 'arc_st_attach', 'Could not create attachment.' );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		if ( $metadata ) {
			wp_update_attachment_metadata( $attach_id, $metadata );
		}
		if ( $alt ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}

		$map[ $filename ] = $attach_id;
		update_option( self::OPTION, $map, false );

		return array(
			'id'  => (int) $attach_id,
			'url' => (string) wp_get_attachment_url( $attach_id ),
		);
	}

	/**
	 * Imports every image a template references.
	 *
	 * @param string $slug Template slug.
	 * @return array filename => array{id:int,url:string} (failures skipped).
	 */
	public static function import_for_template( $slug ) {
		$out               = array();
		$alts              = Arc_ST_Templates::image_alts( $slug );
		self::$last_errors = array();
		foreach ( Arc_ST_Templates::image_files( $slug ) as $file ) {
			$r = self::import( $file, isset( $alts[ $file ] ) ? $alts[ $file ] : '' );
			if ( is_wp_error( $r ) ) {
				self::$last_errors[ $file ] = $r->get_error_message();
			} else {
				$out[ $file ] = $r;
			}
		}
		return $out;
	}

	/**
	 * filename => error for the last import_for_template() call.
	 *
	 * @return array
	 */
	public static function last_errors() {
		return self::$last_errors;
	}
}
