<?php
/**
 * convert-webp.php — converts the bundled arc-stock-*.jpg photos in
 * assets/img/ to WebP (GD) and reports the savings. Pass --delete to
 * remove the JPEG originals after a successful conversion.
 *
 * Template documents and the manifest reference the files by name, so
 * update `arc-stock-NN.jpg` references to `.webp` after converting
 * (scripts do this automatically — see below).
 *
 * Usage: php scripts/convert-webp.php [--delete]
 *
 * @package ARC_Starter_Templates
 */

$dir    = dirname( __DIR__ ) . '/assets/img';
$delete = in_array( '--delete', $argv, true );

if ( ! function_exists( 'imagewebp' ) ) {
	fwrite( STDERR, "GD has no WebP support in this PHP build.\n" );
	exit( 1 );
}

$files  = glob( $dir . '/arc-stock-*.jpg' ) ?: array();
$before = 0;
$after  = 0;
$failed = array();

foreach ( $files as $jpg ) {
	$before += filesize( $jpg );
	$webp    = preg_replace( '/\.jpe?g$/i', '.webp', $jpg );

	$im = imagecreatefromjpeg( $jpg );
	if ( false === $im ) {
		$failed[] = basename( $jpg ) . ' (decode)';
		continue;
	}
	imagepalettetotruecolor( $im );
	$ok = imagewebp( $im, $webp, 82 );
	imagedestroy( $im );

	if ( ! $ok ) {
		$failed[] = basename( $jpg ) . ' (encode)';
		continue;
	}
	$after += filesize( $webp );
	if ( $delete ) {
		unlink( $jpg );
	}
}

printf(
	"Converted %d files — %.1f MB -> %.1f MB (%.0f%% smaller)%s\n",
	count( $files ) - count( $failed ),
	$before / 1048576,
	$after / 1048576,
	$before ? 100 - ( $after / $before ) * 100 : 0,
	$delete ? ' — JPEG originals deleted' : ''
);
if ( $failed ) {
	echo "Failed: " . implode( ', ', $failed ) . "\n";
	exit( 1 );
}
