<?php
/**
 * ARC template CTA links — Customizer-driven destinations for the buttons
 * inside imported ARC Starter Templates pages.
 *
 * ARC Careers exposes three portals (careers / partners / contact), each
 * anchored to a WordPress page. This module lets the Customizer point each
 * CTA group at one of those portals — or at a custom URL — and rewrites the
 * button hrefs on imported template pages at render time. Everything falls
 * back to the bundled template link when the plugin or the setting is not
 * available, so the theme stays loosely coupled to ARC Careers.
 *
 * @package LienzoAstra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'lienzo_arc_portal_types' ) ) {
	/**
	 * Portal types offered as CTA destinations. Defaults to ARC Careers'
	 * canonical three so the controls stay valid while the plugin is off.
	 *
	 * @return string[]
	 */
	function lienzo_arc_portal_types() {
		if ( function_exists( 'arc_careers_portal_types' ) ) {
			return (array) arc_careers_portal_types();
		}
		return array( 'careers', 'partners', 'contact' );
	}
}

if ( ! function_exists( 'lienzo_arc_portal_url' ) ) {
	/**
	 * Permalink of an ARC Careers portal page, or '' when the plugin is
	 * inactive / the portal page has not been created yet.
	 *
	 * @param string $type Portal type (careers|partners|contact).
	 * @return string
	 */
	function lienzo_arc_portal_url( $type ) {
		if ( ! function_exists( 'arc_careers_option' ) || ! function_exists( 'arc_careers_portal_page_key' ) ) {
			return '';
		}
		$page_id = (int) arc_careers_option( arc_careers_portal_page_key( $type ) );
		$url     = $page_id ? get_permalink( $page_id ) : '';

		return $url ? (string) $url : '';
	}
}

if ( ! function_exists( 'lienzo_arc_cta_choices' ) ) {
	/**
	 * Select choices for a CTA-target control: template default, every ARC
	 * Careers portal (with its resolved permalink for clarity) and custom.
	 *
	 * @return array<string,string>
	 */
	function lienzo_arc_cta_choices() {
		$choices = array( 'default' => __( 'Template link (default)', 'lienzo-astra' ) );
		foreach ( lienzo_arc_portal_types() as $type ) {
			$label = function_exists( 'arc_careers_portal_label' ) ? arc_careers_portal_label( $type ) : ucfirst( $type );
			$url   = lienzo_arc_portal_url( $type );
			/* translators: 1: portal label, 2: portal permalink. */
			$choices[ $type ] = '' !== $url
				? sprintf( __( 'ARC Careers: %1$s — %2$s', 'lienzo-astra' ), $label, $url )
				/* translators: %s: portal label. */
				: sprintf( __( 'ARC Careers: %s (page not created)', 'lienzo-astra' ), $label );
		}
		$choices['custom'] = __( 'Custom URL…', 'lienzo-astra' );

		return $choices;
	}
}

if ( ! function_exists( 'lienzoastra_sanitize_arc_link' ) ) {
	/**
	 * Whitelist sanitizer for the CTA-target selects.
	 *
	 * @param string $input Submitted value.
	 * @return string
	 */
	function lienzoastra_sanitize_arc_link( $input ) {
		$choices = lienzo_arc_cta_choices();

		return isset( $choices[ $input ] ) ? $input : 'default';
	}
}

if ( ! function_exists( 'lienzo_arc_cta_link_map' ) ) {
	/**
	 * Resolves the configured destinations — CTA group => URL — for groups
	 * the user pointed somewhere other than the template default.
	 *
	 * @return array<string,string>
	 */
	function lienzo_arc_cta_link_map() {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}

		$map = array();
		foreach ( array( 'primary', 'partner', 'secondary' ) as $kind ) {
			$sel = (string) lienzoastra_get_option( 'arc_tpl_link_' . $kind );
			if ( '' === $sel || 'default' === $sel ) {
				continue;
			}
			$url = 'custom' === $sel
				? esc_url( (string) lienzoastra_get_option( 'arc_tpl_link_' . $kind . '_url' ) )
				: lienzo_arc_portal_url( $sel );
			if ( '' !== $url ) {
				$map[ $kind ] = $url;
			}
		}

		return $map;
	}
}

if ( ! function_exists( 'lienzo_arc_rewrite_cta_hrefs' ) ) {
	/**
	 * Rewrites button/CTA anchor hrefs inside imported template markup.
	 * Anchors are classified by their visible text first (Partner /
	 * Foundation labels form the partner group), then by button class
	 * (btn-primary vs the secondary styles).
	 *
	 * @param string $html Markup.
	 * @param array  $map  CTA group => destination URL.
	 * @return string
	 */
	function lienzo_arc_rewrite_cta_hrefs( $html, $map ) {
		if ( '' === trim( (string) $html ) || ! $map || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		$doc = new DOMDocument();
		@$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ); // phpcs:ignore
		foreach ( $doc->childNodes as $n ) {
			if ( XML_PI_NODE === $n->nodeType ) {
				$doc->removeChild( $n );
				break;
			}
		}

		$xp = new DOMXPath( $doc );
		// Anchors inside third-party plugin markup (intranet, time clock,
		// projects, careers portals) are never rewritten — the mapping only
		// applies to the template's own buttons.
		$anchors = $xp->query( '//a[contains(@class,"btn-") and not(ancestor::*[contains(@class,"arc-etc") or contains(@class,"ixp-") or contains(@class,"ix-") or contains(@class,"intranet") or contains(@class,"arc-portal")])]' );
		foreach ( $anchors as $a ) {
			$text = strtolower( trim( $a->textContent ) );
			$kind = preg_match( '/partner|foundation/', $text )
				? 'partner'
				: ( false !== strpos( (string) $a->getAttribute( 'class' ), 'btn-primary' ) ? 'primary' : 'secondary' );
			if ( isset( $map[ $kind ] ) ) {
				$a->setAttribute( 'href', $map[ $kind ] );
			}
		}

		return $doc->saveHTML();
	}
}

/**
 * Applies the configured CTA destinations on imported ARC template pages.
 *
 * @param string $content Post content.
 * @return string
 */
function lienzo_arc_cta_links_filter( $content ) {
	if ( is_admin() || ! function_exists( 'lienzo_is_arc_template_page' ) || ! lienzo_is_arc_template_page() ) {
		return $content;
	}

	return lienzo_arc_rewrite_cta_hrefs( $content, lienzo_arc_cta_link_map() );
}
add_filter( 'the_content', 'lienzo_arc_cta_links_filter', 100 );
