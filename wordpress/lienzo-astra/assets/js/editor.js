/**
 * Lienzo — Elementor editor.
 *
 * While the "Lienzo Header" / "Lienzo Footer" tabs of Site Settings are open,
 * mirror each control change onto the preview iframe without a page reload.
 */
( function () {
	'use strict';

	const NAMESPACE = 'lienzo';

	/** Toggle the `show` / `hide` helper classes from a switcher value. */
	const toggleShowHide = ( $el, value ) => {
		$el.removeClass( 'hide' ).removeClass( 'show' ).addClass( value ? 'show' : 'hide' );
	};

	/** Swap a prefixed layout class: remove every option, add the chosen one. */
	const toggleLayout = ( $el, prefix, options, value ) => {
		Object.keys( options ).forEach( ( option ) => $el.removeClass( prefix + option ) );

		if ( '' !== value ) {
			$el.addClass( prefix + value );
		}
	};

	/** Control that shows / hides an element. */
	const showHideControl = ( id, selector ) => ( {
		selector,
		callback: ( $el, args ) => toggleShowHide( $el, args.settings[ id ] ),
	} );

	/** Control that swaps a prefixed class on an element. */
	const layoutControl = ( id, selector, prefix ) => ( {
		selector,
		callback: ( $el, args ) => {
			toggleLayout( $el, prefix, args.container.controls[ id ].options, args.settings[ id ] );
		},
	} );

	const getControls = () => ( {
		// Header.
		lienzo_header_logo_display: showHideControl( 'lienzo_header_logo_display', '.site-header .site-logo, .site-header .site-title' ),
		lienzo_header_menu_display: showHideControl( 'lienzo_header_menu_display', '.site-header .site-navigation, .site-header .site-navigation-toggle-holder' ),
		lienzo_header_tagline_display: showHideControl( 'lienzo_header_tagline_display', '.site-header .site-description' ),
		lienzo_header_logo_type: layoutControl( 'lienzo_header_logo_type', '.site-header .site-branding', 'show-' ),
		lienzo_header_layout: layoutControl( 'lienzo_header_layout', '.site-header', 'header-' ),
		lienzo_header_width: layoutControl( 'lienzo_header_width', '.site-header', 'header-' ),
		lienzo_header_menu_dropdown: layoutControl( 'lienzo_header_menu_dropdown', '.site-header', 'menu-dropdown-' ),
		lienzo_header_menu_layout: {
			selector: '.site-header',
			callback: ( $el, args ) => {
				const id = 'lienzo_header_menu_layout';

				// Close the mobile menu before switching layouts.
				$el.find( '.site-navigation-toggle-holder' ).removeClass( 'elementor-active' );
				$el.find( '.site-navigation-dropdown' ).removeClass( 'show' );

				toggleLayout( $el, 'menu-layout-', args.container.controls[ id ].options, args.settings[ id ] );
			},
		},

		// Footer.
		lienzo_footer_logo_display: showHideControl( 'lienzo_footer_logo_display', '.site-footer .site-logo, .site-footer .site-title' ),
		lienzo_footer_tagline_display: showHideControl( 'lienzo_footer_tagline_display', '.site-footer .site-description' ),
		lienzo_footer_menu_display: showHideControl( 'lienzo_footer_menu_display', '.site-footer .site-navigation' ),
		lienzo_footer_logo_type: layoutControl( 'lienzo_footer_logo_type', '.site-footer .site-branding', 'show-' ),
		lienzo_footer_layout: layoutControl( 'lienzo_footer_layout', '.site-footer', 'footer-' ),
		lienzo_footer_width: layoutControl( 'lienzo_footer_width', '.site-footer', 'footer-' ),
		lienzo_footer_copyright_display: {
			selector: '.site-footer .copyright',
			callback: ( $el, args ) => {
				const value = args.settings.lienzo_footer_copyright_display;

				toggleShowHide( $el, value );
				$el.closest( '#site-footer' ).toggleClass( 'footer-has-copyright', 'yes' === value );
			},
		},
		lienzo_footer_copyright_text: {
			selector: '.site-footer .copyright',
			callback: ( $el, args ) => {
				$el.find( 'p' ).text( args.settings.lienzo_footer_copyright_text );
			},
		},
	} );

	class ControlsHook extends $e.modules.hookUI.After {
		getCommand() {
			return 'document/elements/settings';
		}

		getId() {
			return NAMESPACE + '-editor-controls-handler';
		}

		getConditions( args ) {
			const isKit = 'kit' === elementor.documents.getCurrent().config.type;

			if ( ! isKit || ! args.settings ) {
				return false;
			}

			const changed = Object.keys( args.settings );

			// Only react when exactly one of our controls changed.
			return 1 === changed.length && Object.keys( getControls() ).includes( changed[ 0 ] );
		}

		apply( args ) {
			const control = getControls()[ Object.keys( args.settings )[ 0 ] ];
			const $target = elementor.$previewContents.find( control.selector );

			control.callback( $target, args );
		}
	}

	class Component extends $e.modules.ComponentBase {
		pages = {};

		getNamespace() {
			return NAMESPACE;
		}

		defaultHooks() {
			return this.importHooks( { ControlsHook } );
		}
	}

	$e.components.register( new Component() );
}() );
