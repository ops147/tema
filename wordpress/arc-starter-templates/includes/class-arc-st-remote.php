<?php
/**
 * Remote template source — optional mode where the plugin ships without the
 * bundled templates/ + assets/img/ payload and instead pulls the manifest,
 * template documents and images from a remote repository on demand.
 *
 * The remote base URL must point at a directory that mirrors the plugin
 * layout:
 *   <base>/templates/manifest.json
 *   <base>/templates/<demo>/<page>.html
 *   <base>/assets/img/<file>
 * For a public GitHub repo that is
 *   https://raw.githubusercontent.com/<org>/<repo>/<branch>
 * (append a sub-path when the payload lives inside a folder).
 *
 * Downloaded files are cached on disk under uploads/arc-st-remote/ for a
 * TTL (default 6h). A fresh import flushes the cache first, so "Import"
 * always pulls the repo's current state; previews and pattern registration
 * reuse the cache so wp-admin stays fast.
 *
 * Configure it in the library settings screen, via the arc_st_remote_base
 * filter or the ARC_ST_REMOTE_BASE constant.
 *
 * @package ARC_Starter_Templates
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remote repository fetch + disk cache.
 */
final class Arc_ST_Remote {

	const OPTION = 'arc_st_remote_base';

	const TOKEN_OPTION = 'arc_st_remote_token';

	/**
	 * Remote base URL ('' = bundled mode). The constant wins over the option.
	 *
	 * @return string
	 */
	public static function base() {
		$base = defined( 'ARC_ST_REMOTE_BASE' ) && ARC_ST_REMOTE_BASE
			? (string) ARC_ST_REMOTE_BASE
			: (string) get_option( self::OPTION, '' );
		return untrailingslashit( trim( (string) apply_filters( 'arc_st_remote_base', $base ) ) );
	}

	/**
	 * Whether a remote source is configured.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return '' !== self::base();
	}

	/**
	 * Access token for private repositories (e.g. a GitHub PAT with repo
	 * scope — raw.githubusercontent.com accepts it as a Bearer header).
	 * The constant wins over the option; never echoed back to the UI.
	 *
	 * @return string
	 */
	public static function token() {
		$token = defined( 'ARC_ST_REMOTE_TOKEN' ) && ARC_ST_REMOTE_TOKEN
			? (string) ARC_ST_REMOTE_TOKEN
			: (string) get_option( self::TOKEN_OPTION, '' );
		return trim( (string) apply_filters( 'arc_st_remote_token', $token ) );
	}

	/**
	 * Repo-relative path sanitized against traversal segments.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	private static function rel( $path ) {
		$path = ltrim( str_replace( '\\', '/', (string) $path ), '/' );
		return (string) preg_replace( '#(?:^|/)\.\.(?=/|$)#', '', $path );
	}

	/**
	 * Public URL of a repo file.
	 *
	 * @param string $path Repo-relative path (e.g. assets/img/hero.png).
	 * @return string
	 */
	public static function url( $path ) {
		return self::base() . '/' . self::rel( $path );
	}

	/**
	 * Disk-cache root for the current base URL.
	 *
	 * @return string Absolute directory path.
	 */
	private static function cache_dir() {
		$up = wp_upload_dir();
		return trailingslashit( $up['basedir'] ) . 'arc-st-remote/' . md5( self::base() );
	}

	/**
	 * Seconds a cached file is trusted. Default 6h; arc_st_remote_ttl filter.
	 *
	 * @return int
	 */
	public static function ttl() {
		return (int) apply_filters( 'arc_st_remote_ttl', 6 * HOUR_IN_SECONDS );
	}

	/**
	 * Downloads a repo file into the cache and returns its local path.
	 * Falls back to a stale cached copy when the fetch fails, so a hiccup
	 * on the remote never leaves a page empty.
	 *
	 * @param string $path  Repo-relative path (e.g. templates/manifest.json).
	 * @param bool   $force Bypass the TTL and re-download.
	 * @return string Absolute local path, '' when unavailable.
	 */
	public static function fetch( $path, $force = false ) {
		$rel  = self::rel( $path );
		$dest = self::cache_dir() . '/' . $rel;

		if ( ! $force && is_readable( $dest ) && ( time() - (int) filemtime( $dest ) < self::ttl() ) ) {
			return $dest;
		}

		$args  = array(
			'timeout'    => 30,
			'user-agent' => 'ARC-Starter-Templates/' . ARC_ST_VERSION,
		);
		$token = self::token();
		if ( '' !== $token ) {
			$args['headers'] = array( 'Authorization' => 'Bearer ' . $token );
		}

		$res = wp_remote_get( self::url( $rel ), $args );
		if ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
			$body = (string) wp_remote_retrieve_body( $res );
			if ( '' !== $body && wp_mkdir_p( dirname( $dest ) ) ) {
				file_put_contents( $dest, $body, LOCK_EX );
				return $dest;
			}
		}
		return is_readable( $dest ) ? $dest : '';
	}

	/**
	 * A tag that changes when the remote source or its manifest does — used
	 * to bust cached artefacts (e.g. registered block patterns).
	 *
	 * @return string
	 */
	public static function cache_tag() {
		if ( ! self::enabled() ) {
			return 'bundled';
		}
		$manifest = Arc_ST_Templates::manifest();
		return md5( self::base() . '|' . (string) ( isset( $manifest['generated'] ) ? $manifest['generated'] : '' ) );
	}

	/**
	 * Drops every cached download (all bases).
	 */
	public static function flush() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'arc-st-remote';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $it as $file ) {
			if ( $file->isDir() ) {
				@rmdir( $file->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			} else {
				@unlink( $file->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
}
