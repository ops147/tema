<?php
/**
 * Performance and security housekeeping.
 *
 * Small, well-known, easily reversible tweaks. Every group can be switched
 * off with a filter, or from Appearance > Lienzo.
 *
 * @package Lienzo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Performance: trim a handful of default <head> requests/markup that most
 * sites never use, and make sure our own scripts don't block rendering.
 */
if ( ! function_exists( 'lienzo_performance_tweaks' ) ) {
	/**
	 * Register the performance tweaks.
	 *
	 * @return void
	 */
	function lienzo_performance_tweaks() {
		if ( ! apply_filters( 'lienzo_performance_tweaks', true ) ) {
			return;
		}

		// One less request per page: emoji detection script/styles.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'tiny_mce_plugins', 'lienzo_disable_emoji_tinymce' );

		// Head clutter most sites don't use.
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

		// Dashicons are only needed for the admin bar.
		add_action( 'wp_enqueue_scripts', 'lienzo_maybe_dequeue_dashicons', 20 );

		// Don't block rendering on our own front-end script.
		add_filter( 'script_loader_tag', 'lienzo_defer_own_scripts', 10, 2 );
	}
}
add_action( 'init', 'lienzo_performance_tweaks' );

if ( ! function_exists( 'lienzo_disable_emoji_tinymce' ) ) {
	/**
	 * Remove the emoji plugin from TinyMCE.
	 *
	 * @param array $plugins TinyMCE plugins.
	 *
	 * @return array
	 */
	function lienzo_disable_emoji_tinymce( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
	}
}

if ( ! function_exists( 'lienzo_maybe_dequeue_dashicons' ) ) {
	/**
	 * Drop dashicons on the front end for visitors who won't see the admin bar.
	 *
	 * @return void
	 */
	function lienzo_maybe_dequeue_dashicons() {
		// Only dequeue for anonymous visitors: logged-in users may see
		// third-party front-end UI (e.g. intranet-plugin widgets) that
		// relies on dashicons even when the admin bar is hidden.
		if ( ! is_user_logged_in() && ! is_admin_bar_showing() ) {
			wp_dequeue_style( 'dashicons' );
		}
	}
}

if ( ! function_exists( 'lienzo_defer_own_scripts' ) ) {
	/**
	 * Add the `defer` attribute to Lienzo's own front-end scripts.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	function lienzo_defer_own_scripts( $tag, $handle ) {
		$deferred = [ 'lienzo-frontend', 'lienzo-enhancements' ];

		if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, 'defer' ) ) {
			$tag = str_replace( ' src=', ' defer src=', $tag );
		}

		return $tag;
	}
}

/**
 * Security: a few uncontroversial hardening steps.
 */
if ( ! function_exists( 'lienzo_security_hardening' ) ) {
	/**
	 * Register the security tweaks.
	 *
	 * @return void
	 */
	function lienzo_security_hardening() {
		if ( ! apply_filters( 'lienzo_security_hardening', true ) ) {
			return;
		}

		// Remove the theme/WP generator tags (mildly reduces fingerprinting).
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );

		// Pingbacks: disable the XML-RPC methods and the advertising header.
		add_filter( 'xmlrpc_methods', 'lienzo_disable_pingback_methods' );
		add_filter( 'wp_headers', 'lienzo_remove_pingback_header' );

		// Generic login error message (don't reveal whether a username exists).
		add_filter( 'login_errors', 'lienzo_generic_login_error' );

		// Don't expose the full user list to anonymous REST requests.
		add_filter( 'rest_endpoints', 'lienzo_restrict_rest_user_listing' );
	}
}
add_action( 'init', 'lienzo_security_hardening' );

if ( ! function_exists( 'lienzo_disable_pingback_methods' ) ) {
	/**
	 * Remove pingback-related XML-RPC methods.
	 *
	 * @param array $methods XML-RPC methods.
	 *
	 * @return array
	 */
	function lienzo_disable_pingback_methods( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}
}

if ( ! function_exists( 'lienzo_remove_pingback_header' ) ) {
	/**
	 * Remove the X-Pingback response header.
	 *
	 * @param array $headers Response headers.
	 *
	 * @return array
	 */
	function lienzo_remove_pingback_header( $headers ) {
		unset( $headers['X-Pingback'] );

		return $headers;
	}
}

if ( ! function_exists( 'lienzo_generic_login_error' ) ) {
	/**
	 * Replace WordPress's specific login error with a generic one.
	 *
	 * @return string
	 */
	function lienzo_generic_login_error() {
		return esc_html__( 'Incorrect username or password.', 'lienzo-astra' );
	}
}

if ( ! function_exists( 'lienzo_restrict_rest_user_listing' ) ) {
	/**
	 * Require authentication to list users through the REST API.
	 *
	 * A single user can still be fetched by ID (many plugins rely on that);
	 * only the anonymous "list everyone" collection endpoint is restricted.
	 *
	 * @param array $endpoints REST endpoints.
	 *
	 * @return array
	 */
	function lienzo_restrict_rest_user_listing( $endpoints ) {
		if ( is_user_logged_in() || empty( $endpoints['/wp/v2/users'] ) ) {
			return $endpoints;
		}

		foreach ( $endpoints['/wp/v2/users'] as $key => $handler ) {
			if ( isset( $handler['methods']['GET'] ) ) {
				unset( $endpoints['/wp/v2/users'][ $key ] );
			}
		}

		return $endpoints;
	}
}
