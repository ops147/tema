/**
 * Live preview bindings for Design Options settings that use the
 * 'postMessage' transport, so color/size sliders update instantly inside
 * the Customizer preview without a full page reload.
 *
 * @package LienzoAstra
 */
( function ( wp ) {
	if ( ! wp || ! wp.customize ) {
		return;
	}

	function setVar( name, value ) {
		document.documentElement.style.setProperty( name, value );
	}

	var bindings = {
		lienzoastra_color_primary: '--lienzoastra-color-primary',
		lienzoastra_color_link: '--lienzoastra-color-link',
		lienzoastra_color_text: '--lienzoastra-color-text',
		lienzoastra_color_background: '--lienzoastra-color-background',
		lienzoastra_footer_color_background: '--lienzoastra-footer-bg',
		lienzoastra_footer_color_text: '--lienzoastra-footer-text',
	};

	// Design Option setting -> block editor preset variable, so synced
	// palette colors used by blocks update live too.
	var presetBindings = {
		lienzoastra_color_primary: '--wp--preset--color--primary',
		lienzoastra_color_text: '--wp--preset--color--secondary',
		lienzoastra_color_background: '--wp--preset--color--light',
	};

	// Optional color settings: an empty pick means "fall back", matching
	// the fallbacks baked into lienzoastra_chrome_css() / customizer_css().
	var fallbackBindings = {
		lienzoastra_color_link_hover: [ '--lienzoastra-color-link-hover', '--lienzoastra-color-link' ],
		lienzoastra_color_heading: [ '--lienzoastra-color-heading', '--lienzoastra-color-text' ],
		lienzoastra_header_bg_color: [ '--lienzoastra-header-bg', 'transparent' ],
		lienzoastra_header_border_color: [ '--lienzoastra-header-border-color', '#e5e7eb' ],
		lienzoastra_menu_link_color: [ '--lienzoastra-menu-color', 'inherit' ],
		lienzoastra_menu_link_hover_color: [ '--lienzoastra-menu-color-hover', 'var(--color-brand, var(--lienzoastra-color-link, #2563eb))' ],
		lienzoastra_toggle_color: [ '--lienzoastra-toggle-color', 'inherit' ],
		lienzoastra_dropdown_bg_color: [ '--lienzoastra-dropdown-bg', '#ffffff' ],
		lienzoastra_dropdown_text_color: [ '--lienzoastra-dropdown-color', 'inherit' ],
		lienzoastra_footer_link_color: [ '--lienzoastra-footer-link', 'var(--lienzoastra-footer-text)' ],
	};

	// Settings that become a <length> custom property (suffix appended).
	var sizeBindings = {
		lienzoastra_font_size_base: [ '--lienzoastra-font-size-base', 'px' ],
		lienzoastra_container_width: [ '--lienzoastra-container-width', 'px' ],
		lienzoastra_content_width: [ '--lienzoastra-content-width', 'px' ],
		lienzoastra_header_padding: [ '--lienzoastra-header-padding', 'px' ],
		lienzoastra_menu_item_spacing: [ '--lienzoastra-menu-gap', 'px' ],
		lienzoastra_menu_font_size: [ '--lienzoastra-menu-font-size', 'px' ],
		lienzoastra_footer_padding: [ '--lienzoastra-footer-padding', 'px' ],
	};

	Object.keys( bindings ).forEach( function ( setting ) {
		wp.customize( setting, function ( value ) {
			value.bind( function ( newValue ) {
				setVar( bindings[ setting ], newValue );
				if ( presetBindings[ setting ] ) {
					setVar( presetBindings[ setting ], newValue );
				}
				// Link color also feeds the hover fallback when unset.
				if ( 'lienzoastra_color_link' === setting ) {
					var hover = wp.customize( 'lienzoastra_color_link_hover' );
					if ( hover && ! hover.get() ) {
						setVar( '--lienzoastra-color-link-hover', newValue );
					}
				}
				if ( 'lienzoastra_color_text' === setting ) {
					var heading = wp.customize( 'lienzoastra_color_heading' );
					if ( heading && ! heading.get() ) {
						setVar( '--lienzoastra-color-heading', newValue );
					}
				}
				if ( 'lienzoastra_footer_color_text' === setting ) {
					var flink = wp.customize( 'lienzoastra_footer_link_color' );
					if ( flink && ! flink.get() ) {
						setVar( '--lienzoastra-footer-link', newValue );
					}
				}
			} );
		} );
	} );

	Object.keys( fallbackBindings ).forEach( function ( setting ) {
		wp.customize( setting, function ( value ) {
			value.bind( function ( newValue ) {
				var pair = fallbackBindings[ setting ];
				setVar( pair[0], newValue ? newValue : pair[1] );
			} );
		} );
	} );

	Object.keys( sizeBindings ).forEach( function ( setting ) {
		wp.customize( setting, function ( value ) {
			value.bind( function ( newValue ) {
				var pair = sizeBindings[ setting ];
				setVar( pair[0], newValue + pair[1] );
			} );
		} );
	} );

	wp.customize( 'lienzoastra_color_palette', function ( value ) {
		value.bind( function ( newValue ) {
			var palettes = window.lienzoastraPalettes || {};
			var palette = palettes[ newValue ];
			if ( ! palette ) {
				return;
			}
			Object.keys( palette ).forEach( function ( key ) {
				var setting = wp.customize( 'lienzoastra_' + key );
				if ( setting ) {
					setting.set( palette[ key ] );
				}
			} );
		} );
	} );

	wp.customize( 'lienzoastra_line_height_base', function ( value ) {
		value.bind( function ( newValue ) {
			setVar( '--lienzoastra-line-height', newValue );
		} );
	} );

	wp.customize( 'lienzoastra_logo_width', function ( value ) {
		value.bind( function ( newValue ) {
			setVar( '--lienzoastra-logo-width', newValue > 0 ? newValue + 'px' : 'auto' );
		} );
	} );

	wp.customize( 'lienzoastra_logo_width_mobile', function ( value ) {
		value.bind( function ( newValue ) {
			var id = 'lienzoastra-logo-mobile-preview';
			var style = document.getElementById( id );
			if ( newValue > 0 ) {
				if ( ! style ) {
					style = document.createElement( 'style' );
					style.id = id;
					document.head.appendChild( style );
				}
				style.textContent = '@media (max-width:767px){.site-header .site-branding .custom-logo{max-width:' + newValue + 'px;width:' + newValue + 'px}}';
			} else if ( style ) {
				style.textContent = '';
			}
		} );
	} );

	wp.customize( 'lienzoastra_header_border', function ( value ) {
		value.bind( function ( newValue ) {
			setVar( '--lienzoastra-header-border-width', newValue ? '1px' : '0' );
		} );
	} );

	wp.customize( 'lienzoastra_menu_alignment', function ( value ) {
		value.bind( function ( newValue ) {
			var map = { left: 'flex-start', center: 'center', right: 'flex-end' };
			setVar( '--lienzoastra-menu-align', map[ newValue ] || 'flex-end' );
		} );
	} );

	wp.customize( 'lienzoastra_menu_font_weight', function ( value ) {
		value.bind( function ( newValue ) {
			setVar( '--lienzoastra-menu-font-weight', newValue );
		} );
	} );

	wp.customize( 'lienzoastra_menu_text_transform', function ( value ) {
		value.bind( function ( newValue ) {
			setVar( '--lienzoastra-menu-text-transform', newValue );
		} );
	} );

	wp.customize( 'lienzoastra_header_menu_breakpoint', function ( value ) {
		value.bind( function ( newValue ) {
			var header = document.querySelector( '.site-header' );
			if ( ! header ) {
				return;
			}
			header.classList.remove(
				'menu-dropdown-mobile',
				'menu-dropdown-tablet',
				'menu-dropdown-none',
				'menu-layout-dropdown'
			);
			header.classList.add( newValue );
		} );
	} );
} )( window.wp );
