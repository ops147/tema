/**
 * Lienzo — front end.
 *
 * Toggles the dropdown (mobile) navigation of the dynamic header.
 * Keeps aria-expanded / aria-hidden / inert in sync so the closed menu is
 * never reachable by keyboard or screen readers.
 */
( function () {
	'use strict';

	const ACTIVE = 'elementor-active';

	class HeaderMenu {
		constructor() {
			this.toggle = document.querySelector( '.site-header .site-navigation-toggle' );
			this.holder = document.querySelector( '.site-header .site-navigation-toggle-holder' );
			this.dropdown = document.querySelector( '.site-header .site-navigation-dropdown' );

			// Nothing to do when the menu is hidden or absent.
			if ( ! this.toggle || ! this.holder || ! this.dropdown || this.holder.classList.contains( 'hide' ) ) {
				return;
			}

			this.onResize = () => this.close();

			this.toggle.addEventListener( 'click', () => this.handleToggle() );

			this.dropdown
				.querySelectorAll( '.menu-item-has-children > a' )
				.forEach( ( link ) => link.addEventListener( 'click', ( event ) => this.handleChildren( event ) ) );
		}

		isOpen() {
			return this.holder.classList.contains( ACTIVE );
		}

		open() {
			this.setState( true );
			window.addEventListener( 'resize', this.onResize );
		}

		close() {
			this.setState( false );
			window.removeEventListener( 'resize', this.onResize );
		}

		setState( open ) {
			this.toggle.setAttribute( 'aria-expanded', String( open ) );
			this.dropdown.setAttribute( 'aria-hidden', String( ! open ) );
			this.dropdown.inert = ! open;
			this.holder.classList.toggle( ACTIVE, open );

			// Collapse any open sub-menu whenever the state changes.
			this.dropdown.querySelectorAll( '.' + ACTIVE ).forEach( ( item ) => item.classList.remove( ACTIVE ) );
		}

		handleToggle() {
			if ( this.isOpen() ) {
				this.close();
			} else {
				this.open();
			}
		}

		handleChildren( event ) {
			const item = event.currentTarget.parentElement;

			if ( item && item.classList ) {
				item.classList.toggle( ACTIVE );
			}
		}
	}

	// Floating scroll-to-top button (printed by the theme when the
	// Design Options toggle is on — absent otherwise).
	function initScrollTop() {
		const button = document.querySelector( '.lienzoastra-scroll-top' );

		if ( ! button ) {
			return;
		}

		const onScroll = () => button.classList.toggle( 'lienzoastra-visible', window.scrollY > 300 );

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();

		button.addEventListener( 'click', () => window.scrollTo( { top: 0, behavior: 'smooth' } ) );
	}

	function init() {
		new HeaderMenu();
		initScrollTop();
	}

	// Bind immediately when the script arrives late (deferred/lazy-loaded
	// after parsing) — DOMContentLoaded only fires once, so waiting for it
	// again would leave the toggle dead.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
