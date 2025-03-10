( function( $, api ) {
	var $window = $( window ),
		$document = $( document ),
		$body = $( 'body' );
	/**
	 * API on ready event handlers
	 *
	 * All handlers need to be inside the 'ready' state.
	 */
	wp.customize.bind( 'ready', function() {
		$( 'input[name=cryptozfree-flush-local-fonts-button]' ).on( 'click', function( e ) {
			var data = {
				wp_customize: 'on',
				action: 'cryptozfree_flush_fonts_folder',
				nonce: cryptozfreeCustomizerControlsData.flushFonts
			};	
			$( 'input[name=cryptozfree-flush-local-fonts-button]' ).attr('disabled', 'disabled');
	
			$.post( ajaxurl, data, function ( response ) {
				console.log( response );
				if ( response && response.success ) {
					$( 'input[name=cryptozfree-flush-local-fonts-button]' ).val( 'Successfully Flushed' );
				} else {
					$( 'input[name=cryptozfree-flush-local-fonts-button]' ).val( 'Failed, Reload Page and Try Again' );
				}
			});
		});

		wp.customize.state.create( 'cryptozfreeTab' );
		wp.customize.state( 'cryptozfreeTab' ).set( 'general' );
		

		// Set handler when custom responsive toggle is clicked.
		$( '#customize-theme-controls' ).on( 'click', '.cryptozfree-build-tabs-button:not(.cryptozfree-nav-tabs-button)', function( e ) {
			e.preventDefault();

			wp.customize.previewedDevice.set( $( this ).attr( 'data-device' ) );
		});
		// Set handler when custom responsive toggle is clicked.
		$( '#customize-theme-controls' ).on( 'click', '.cryptozfree-compontent-tabs-button:not(.cryptozfree-nav-tabs-button)', function( e ) {
			e.preventDefault();

			wp.customize.state( 'cryptozfreeTab' ).set( $( this ).attr( 'data-tab' ) );
		});
		var setCustomTabElementsDisplay = function() {
			var tabState = wp.customize.state( 'cryptozfreeTab' ).get(),
			$tabs = $( '.cryptozfree-compontent-tabs-button:not(.cryptozfree-nav-tabs-button)' );
			$tabs.removeClass( 'nav-tab-active' ).filter( '.cryptozfree-' + tabState + '-tab' ).addClass( 'nav-tab-active' );
		}
		// Refresh all responsive elements when previewedDevice is changed.
		wp.customize.state( 'cryptozfreeTab' ).bind( setCustomTabElementsDisplay );

		$( '#customize-theme-controls' ).on( 'click', 'customize-section-back', function( e ) {
			wp.customize.state( 'cryptozfreeTab' ).set( 'general' );
		});
		if ( cryptozfreeCustomizerControlsData && cryptozfreeCustomizerControlsData.contexts ) {
			/**
			 * Active callback script (JS version)
			 * ref: https://make.xwp.co/2016/07/24/dependently-contextual-customizer-controls/
			 */
			_.each( cryptozfreeCustomizerControlsData.contexts, function( rules, key ) {
				var getSetting = function( settingName ) {
					// Get the dependent setting.
					switch ( settingName ) {
						case '__device':
							return wp.customize.previewedDevice;
							break;
						case '__current_tab':
							return wp.customize.state( 'cryptozfreeTab' );
							break;
						default:
							return wp.customize( settingName );
							break;
					}
				}
				var initContext = function( element ) {
					// Main function returning the conditional value
					var isDisplayed = function() {
						var displayed = false,
						    relation = rules['relation'];

						// Fallback invalid relation type to "AND".
						// Assign default displayed to true for "AND" relation type.
						if ( 'OR' !== relation ) {
							relation = 'AND';
							displayed = true;
						}

						// Each rule iteration
						_.each( rules, function( rule, i ) {
							// Skip "relation" property.
							if ( 'relation' == i ) return;

							// If in "AND" relation and "displayed" already flagged as false, skip the rest rules.
							if ( 'AND' == relation && false == displayed ) return;

							// Skip if no setting propery found.
							if ( undefined === rule['setting'] ) return;

							var result = false,
							    setting = getSetting( rule['setting'] );
							// Only process the rule if dependent setting is found.
							// Otherwise leave the result to "false".
							if ( undefined !== setting ) {
								var operator = rule['operator'],
								    comparedValue = rule['value'],
									currentValue = setting.get();
								if ( undefined == operator || '=' == operator ) {
									operator = '==';
								}

								if ( 'sub_object_contains' === operator ) {
									if ( undefined !== currentValue[ rule['sub_key'] ] ) {
										currentValue = currentValue[ rule['sub_key'] ];
									}
								}
								if ( 'sub_object_does_not_contain' === operator ) {
									if ( undefined !== currentValue[ rule['sub_key'] ] ) {
										currentValue = currentValue[ rule['sub_key'] ];
									}
								}
								switch ( operator ) {
									case '>':
										result = currentValue > comparedValue; 
										break;

									case '<':
										result = currentValue < comparedValue; 
										break;

									case '>=':
										result = currentValue >= comparedValue; 
										break;

									case '<=':
										result = currentValue <= comparedValue; 
										break;

									case 'in':
										result = 0 <= comparedValue.indexOf( currentValue );
										break;

									case 'not_in':
										result = 0 > comparedValue.indexOf( currentValue );
										break;

									case 'contain':
										//result = ( currentValue.includes( comparedValue ) );
										result = 0 <= currentValue.indexOf( comparedValue );
										break;

									case 'not_contain':
										result = 0 > currentValue.indexOf( comparedValue );
										break;

									case 'in':
										result = 0 <= comparedValue.indexOf( currentValue );
										break;
	
									case 'array_includes':
										result = currentValue.includes( comparedValue );
										break;
									case 'sub_object_does_not_contain':
										if ( rule['responsive'] ) {
											result = true;
											{ Object.keys( { 'desktop':'', 'tablet':'', 'mobile':'' } ).map( ( device ) => {
												if ( currentValue[ device ].includes( comparedValue ) ) {
													result = false;
												}
											} ) }
										} else {
											result = ! currentValue.includes( comparedValue );
										}
										break;
									case 'sub_object_contains':
										if ( rule['responsive'] ) {
											{ Object.keys( { 'desktop':'', 'tablet':'', 'mobile':'' } ).map( ( device ) => {
												if ( currentValue[ device ].includes( comparedValue ) ) {
													result = true;
												}
											} ) }
										} else {
											result = currentValue.includes( comparedValue );
										}
										break;

									case 'empty':
										result = (currentValue === undefined || currentValue == null || currentValue.length <= 0);
										//result = 0 == currentValue.length;
										break;

									case '!empty':
										result = typeof currentValue !== 'undefined' && undefined !== currentValue && null !== currentValue && '' !== currentValue;
										//result = 0 < currentValue.length;
										break;
									case '!=':
										result = comparedValue !== currentValue;
										//result = 0 < currentValue.length;
										break;
									case 'load_italic':
										result = false;
										if ( currentValue['family'] && currentValue['google'] && currentValue['variant'] ) {
											if ( 0 > currentValue['variant'].indexOf( 'italic' ) ) {
												if ( cryptozfreeCustomizerControlsData.gfontvars && cryptozfreeCustomizerControlsData.gfontvars[ currentValue['family'] ] && cryptozfreeCustomizerControlsData.gfontvars[ currentValue['family'] ].v && cryptozfreeCustomizerControlsData.gfontvars[ currentValue['family'] ].v.includes( 'italic' ) ) {
													result = true;
												}
											}
										}
										break;
									default:
										result = comparedValue == currentValue;
										break;
								}
							}

							// Combine to the final result.
							switch ( relation ) {
								case 'OR':
									displayed = displayed || result;
									break;

								default:
									displayed = displayed && result;
									break;
							}
						});

						return displayed;
					};

					// Wrapper function for binding purpose
					var setActiveState = function() {
						element.active.set( isDisplayed() );
					};

					// Setting changes bind
					_.each( rules, function( rule, i ) {
						// Skip "relation" property.
						if ( 'relation' == i ) return;
						var setting = getSetting( rule['setting'] );

						if ( undefined !== setting ) {
							// Bind the setting for future use.
							setting.bind( setActiveState );
						}
					});

					// Initial run
					element.active.validate = isDisplayed;
					setActiveState();
				};

				if ( 0 == key.indexOf( 'cryptozfree_customizer' ) ) {
					wp.customize.section( key, initContext );
				} else {
					wp.customize.control( key, initContext );
				}
			});
		}

		// Set all custom responsive toggles and fieldsets.
		var setCustomResponsiveElementsDisplay = function() {
			var device = wp.customize.previewedDevice.get(),
			    $tabs = $( '.cryptozfree-build-tabs-button.nav-tab' );
			$tabs.removeClass( 'nav-tab-active' ).filter( '.preview-' + device ).addClass( 'nav-tab-active' );
		}
		// Refresh all responsive elements when previewedDevice is changed.
		wp.customize.previewedDevice.bind( setCustomResponsiveElementsDisplay );

		// Refresh all responsive elements when any section is expanded.
		// This is required to set responsive elements on newly added controls inside the section.
		wp.customize.section.each(function ( section ) {
			section.expanded.bind( setCustomResponsiveElementsDisplay );
		});

		/**
		 * Resize Preview Frame when show / hide Builder.
		 */
		var resizePreviewer = function() {
			var $section = $( '.control-section.cryptozfree-builder-active' );
			var $footer = $( '.control-section.cryptozfree-footer-builder-active' );
			if ( $body.hasClass( 'cryptozfree-builder-is-active' ) || $body.hasClass( 'cryptozfree-footer-builder-is-active' ) ) {
				if ( $body.hasClass( 'cryptozfree-footer-builder-is-active' ) && 0 < $footer.length && ! $footer.hasClass( 'cryptozfree-builder-hide' ) ) {
					wp.customize.previewer.container.css( 'bottom', $footer.outerHeight() + 'px' );
				} else if ( $body.hasClass( 'cryptozfree-builder-is-active' ) && 0 < $section.length && ! $section.hasClass( 'cryptozfree-builder-hide' ) ) {
					wp.customize.previewer.container.css({ "bottom" : $section.outerHeight() + 'px' });
				} else {
					wp.customize.previewer.container.css( 'bottom', '');
				}
			} else {
				wp.customize.previewer.container.css( 'bottom', '');
			}
		}
		$window.on( 'resize', resizePreviewer );
		wp.customize.previewedDevice.bind(function( device ) {
			setTimeout(function() {
				resizePreviewer();
			}, 250 );
		});
		var reloadPreviewer = function() {
			$( wp.customize.previewer.container ).find( 'iframe' ).css( 'position', 'static' );
			$( wp.customize.previewer.container ).find( 'iframe' ).css( 'position', 'absolute' );
		}
		wp.customize.previewer.bind( 'ready', reloadPreviewer );
		/**
		 * Init Header & Footer Builder
		 */
		var initHeaderBuilderPanel = function( panel ) {
			var section =  wp.customize.section( 'cryptozfree_customizer_header_builder' );
			if ( section ) {
				var $section = section.contentContainer,
				section_layout =  wp.customize.section( 'cryptozfree_customizer_header_layout' );
				// If Header panel is expanded, add class to the body tag (for CSS styling).
				panel.expanded.bind(function( isExpanded ) {
					_.each(section.controls(), function( control ) {
						if ( 'resolved' === control.deferred.embedded.state() ) {
							return;
						}
						control.renderContent();
						control.deferred.embedded.resolve(); // This triggers control.ready().
						
						// Fire event after control is initialized.
						control.container.trigger( 'init' );
					});

					if ( isExpanded ) {
						$body.addClass( 'cryptozfree-builder-is-active' );
						$section.addClass( 'cryptozfree-builder-active' );
						$section.css('display', 'none').height();
						$section.css('display', 'block');
					} else {
						$body.removeClass( 'cryptozfree-builder-is-active' );
						$section.removeClass( 'cryptozfree-builder-active' );
					}
					_.each(section_layout.controls(), function( control ) {
						if ( 'resolved' === control.deferred.embedded.state() ) {
							return;
						}
						control.renderContent();
						control.deferred.embedded.resolve(); // This triggers control.ready().
						
						// Fire event after control is initialized.
						control.container.trigger( 'init' );
					});
					resizePreviewer();
				});
				// Attach callback to builder toggle.
				$section.on( 'click', '.cryptozfree-builder-tab-toggle', function( e ) {
					e.preventDefault();
					$section.toggleClass( 'cryptozfree-builder-hide' );
					resizePreviewer();
				});
			}

		};
		wp.customize.panel( 'cryptozfree_customizer_header', initHeaderBuilderPanel );
		/**
		 * Init Header & Footer Builder
		 */
		var initFooterBuilderPanel = function( panel ) {
			var section =  wp.customize.section( 'cryptozfree_customizer_footer_builder' );
			if ( section ) {
				var $section = section.contentContainer,
				section_layout =  wp.customize.section( 'cryptozfree_customizer_footer_layout' );
				// If Header panel is expanded, add class to the body tag (for CSS styling).
				panel.expanded.bind(function( isExpanded ) {
					_.each(section.controls(), function( control ) {
						if ( 'resolved' === control.deferred.embedded.state() ) {
							return;
						}
						control.renderContent();
						control.deferred.embedded.resolve(); // This triggers control.ready().
						
						// Fire event after control is initialized.
						control.container.trigger( 'init' );
					});

					if ( isExpanded ) {
						$body.addClass( 'cryptozfree-footer-builder-is-active' );
						$section.addClass( 'cryptozfree-footer-builder-active' );
						$section.css('display', 'none').height();
						$section.css('display', 'block');
					} else {
						$body.removeClass( 'cryptozfree-footer-builder-is-active' );
						$section.removeClass( 'cryptozfree-footer-builder-active' );
					}
					_.each(section_layout.controls(), function( control ) {
						if ( 'resolved' === control.deferred.embedded.state() ) {
							return;
						}
						control.renderContent();
						control.deferred.embedded.resolve(); // This triggers control.ready().
						
						// Fire event after control is initialized.
						control.container.trigger( 'init' );
					});
					resizePreviewer();
				});
				// Attach callback to builder toggle.
				$section.on( 'click', '.cryptozfree-builder-tab-toggle', function( e ) {
					e.preventDefault();
					$section.toggleClass( 'cryptozfree-builder-hide' );
					resizePreviewer();
				} );
			}

		};
		wp.customize.panel( 'cryptozfree_customizer_footer', initFooterBuilderPanel );
	});

} )( jQuery, wp );