/**
 * Lienzo — enhancements.
 *
 * Dark mode toggle (persisted in localStorage, respects
 * prefers-color-scheme), back-to-top button, reading progress bar and
 * opt-in scroll reveal (.lienzo-reveal / [data-lienzo-reveal]).
 * Every piece is independent and checks `window.lienzoFeatures` (localized
 * from PHP) before doing anything.
 */
( function () {
	'use strict';

	const settings = window.lienzoFeatures || {};
	const STORAGE_KEY = 'lienzo-theme';

	/** Dark mode -------------------------------------------------------- */

	function initDarkMode() {
		if ( ! settings.darkMode ) {
			return;
		}

		const root = document.documentElement;
		const stored = window.localStorage ? window.localStorage.getItem( STORAGE_KEY ) : null;
		const prefersDark = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;

		const applyTheme = ( theme ) => {
			root.setAttribute( 'data-lienzo-theme', theme );
			document.querySelectorAll( '.lienzo-dark-toggle' ).forEach( ( button ) => {
				button.setAttribute( 'aria-pressed', String( 'dark' === theme ) );
				const label = 'dark' === theme
					? ( settings.i18n && settings.i18n.toggleToLight )
					: ( settings.i18n && settings.i18n.toggleToDark );
				if ( label ) {
					button.setAttribute( 'aria-label', label );
				}
			} );
		};

		applyTheme( stored || ( prefersDark ? 'dark' : 'light' ) );

		document.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '.lienzo-dark-toggle' );
			if ( ! button ) {
				return;
			}

			const next = 'dark' === root.getAttribute( 'data-lienzo-theme' ) ? 'light' : 'dark';
			applyTheme( next );

			if ( window.localStorage ) {
				window.localStorage.setItem( STORAGE_KEY, next );
			}
		} );
	}

	/** Back to top -------------------------------------------------------*/

	function initBackToTop() {
		if ( ! settings.backToTop ) {
			return;
		}

		const button = document.querySelector( '.lienzo-back-to-top' );
		if ( ! button ) {
			return;
		}

		const toggleVisibility = () => {
			button.classList.toggle( 'is-visible', window.scrollY > 300 );
		};

		window.addEventListener( 'scroll', toggleVisibility, { passive: true } );
		toggleVisibility();

		button.addEventListener( 'click', () => {
			window.scrollTo( {
				top: 0,
				behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth',
			} );
		} );
	}

	/** Reading progress bar ----------------------------------------------*/

	function initReadingProgress() {
		if ( ! settings.readingProgress ) {
			return;
		}

		const bar = document.querySelector( '.lienzo-reading-progress-bar' );
		const wrapper = document.querySelector( '.lienzo-reading-progress' );
		if ( ! bar || ! wrapper ) {
			return;
		}

		const update = () => {
			const scrollable = document.documentElement.scrollHeight - window.innerHeight;
			const progress = scrollable > 0 ? Math.min( 100, Math.max( 0, ( window.scrollY / scrollable ) * 100 ) ) : 0;
			bar.style.inlineSize = progress + '%';
			wrapper.setAttribute( 'aria-valuenow', String( Math.round( progress ) ) );
		};

		wrapper.setAttribute( 'aria-valuemin', '0' );
		wrapper.setAttribute( 'aria-valuemax', '100' );

		window.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
	}

	/** Scroll reveal ------------------------------------------------------*/

	function initScrollReveal() {
		if ( ! settings.scrollReveal ) {
			return;
		}

		const els = document.querySelectorAll( '.lienzo-reveal, [data-lienzo-reveal]' );
		if ( ! els.length ) {
			return;
		}

		// No IntersectionObserver -> reveal everything right away.
		if ( ! ( 'IntersectionObserver' in window ) ) {
			els.forEach( ( el ) => el.classList.add( 'is-visible' ) );
			return;
		}

		const io = new IntersectionObserver( ( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' } );

		els.forEach( ( el ) => io.observe( el ) );
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		initDarkMode();
		initBackToTop();
		initReadingProgress();
		initScrollReveal();
	} );
}() );
