import PropTypes from 'prop-types';
const { __ } = wp.i18n;
const { Component } = wp.element;
import map from 'lodash/map';
import ColorControl from '../common/color.js';
import { Fragment } from 'react';
const { ButtonGroup, Dashicon, Tooltip, Button, Popover, TabPanel, TextareaControl } = wp.components;
class ColorComponent extends Component {
	constructor(props) {
		super( props );
		this.handleChangeComplete = this.handleChangeComplete.bind( this );
		this.handleChangePalette = this.handleChangePalette.bind( this );
		this.handleTextImport = this.handleTextImport.bind( this );
		this.handlePresetImport = this.handlePresetImport.bind( this );
		this.updateValues = this.updateValues.bind( this );
		let value = JSON.parse( this.props.control.setting.get() );
		let baseDefault =  ( cryptozfreeCustomizerControlsData.palette ? JSON.parse( cryptozfreeCustomizerControlsData.palette ) : { palette: [] } );
		this.defaultValue = this.props.control.params.default ? {
			...baseDefault,
			...this.props.control.params.default
		} : baseDefault;
		value = value ? {
			...this.defaultValue,
			...value
		} : this.defaultValue;
		let defaultParams = {
			reset: '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#FFFFFF","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#FFFFFF","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#FFFFFF","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}',
			palettes: ( cryptozfreeCustomizerControlsData.palettePresets ? cryptozfreeCustomizerControlsData.palettePresets : [] ),
			colors: {
				palette1: {
					tooltip: __( '1 - Accent', 'cryptozfree' ),
					palette: false,
				},
				palette2: {
					tooltip: __( '2 - Accent - alt', 'cryptozfree' ),
					palette: false,
				},
				palette3: {
					tooltip: __( '3 - Strongest text', 'cryptozfree' ),
					palette: false,
				},
				palette4: {
					tooltip: __( '4 - Strong Text', 'cryptozfree' ),
					palette: false,
				},
				palette5: {
					tooltip: __( '5 - Medium text', 'cryptozfree' ),
					palette: false,
				},
				palette6: {
					tooltip: __( '6 - Subtle Text', 'cryptozfree' ),
					palette: false,
				},
				palette7: {
					tooltip: __( '7 - Subtle Background', 'cryptozfree' ),
					palette: false,
				},
				palette8: {
					tooltip: __( '8 - Lighter Background', 'cryptozfree' ),
					palette: false,
				},
				palette9: {
					tooltip: __( '9 - White or offwhite', 'cryptozfree' ),
					palette: false,
				},
			},
			paletteMap: {
				'palette': {
					'tooltip': __( 'Palette 1', 'cryptozfree' ),
				},
				'second-palette': {
					'tooltip': __( 'Palette 2', 'cryptozfree' ),
				},
				'third-palette': {
					'tooltip': __( 'Palette 3', 'cryptozfree' ),
				}
			}
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		this.state = {
			value: value,
			colorPalette: [],
			fresh: 'start',
			isVisible: false,
			textImport: '',
			importError: false,
		};
	}
	handleChangePalette( active ) {
		let value = this.state.value;
		const newItems = this.state.value[ active ].map( ( item, index ) => {
			document.documentElement.style.setProperty('--global-' + item.slug, item.color );
			return item;
		} );
		value.active  = active;
		value[active] = newItems;
		this.setState( { fresh: ( this.state.fresh !== 'start' ? 'start' : 'second' ) } );
		this.updateValues( value );
	}
	handlePresetImport( preset ) {
		const presetPalettes = JSON.parse( this.controlParams.palettes );
		// Verify data.
		if ( presetPalettes && presetPalettes[preset] && 9 === presetPalettes[preset].length ) {
			const newItems = presetPalettes[preset].map( ( item, index ) => {
				if ( item.color ) {
					this.handleChangeComplete( { hex: item.color }, false, '', index );
				}
			} );
			this.setState( { fresh: ( this.state.fresh !== 'start' ? 'start' : 'second' ), importError:false } );
		} else {
			this.setState( { importPresetError:true } );
		}
	}
	handleTextImport() {
		const importText = this.state.textImport;
		if ( ! importText ) {
			this.setState( { importError:true } );
			return;
		}
		const textImportData = JSON.parse( importText );
		// Verify data.
		if ( textImportData && textImportData instanceof Array && textImportData.length === 9 && textImportData[0] && textImportData[0].color ) {
			const newItems = textImportData.map( ( item, index ) => {
				if ( item.color ) {
					this.handleChangeComplete( { hex: item.color }, false, '', index );
				}
			} );
			this.setState( { fresh: ( this.state.fresh !== 'start' ? 'start' : 'second' ), textImport: '', isVisible:false, importError:false } );
		} else {
			this.setState( { importError:true } );
		}
	}
	handleChangeComplete( color, isPalette, item, index ) {
		let newColor = {};
		if ( undefined !== color.rgb && undefined !== color.rgb.a && 1 !== color.rgb.a ) {
			newColor.color = 'rgba(' +  color.rgb.r + ',' +  color.rgb.g + ',' +  color.rgb.b + ',' + color.rgb.a + ')';
		} else {
			newColor.color = color.hex;
		}
		let value = this.state.value;
		const newItems = this.state.value[this.state.value.active].map( ( item, thisIndex ) => {
			if ( parseInt( index ) === parseInt( thisIndex ) ) {
				item = { ...item, ...newColor };
				document.documentElement.style.setProperty('--global-' + this.state.value[this.state.value.active][index].slug, newColor.color );
			}

			return item;
		} );
		value[this.state.value.active] = newItems;
		this.updateValues( value );
	}

	render() {
		const toggleVisible = () => {
			this.setState( { isVisible: true } );
		};
		const toggleClose = () => {
			if ( this.state.isVisible === true ) {
				this.setState( { isVisible: false } );
			}
		};
		const presetPalettes = JSON.parse( this.controlParams.palettes );
		let currentPaletteData = [];
		Object.keys( this.controlParams.colors ).map( ( item, index ) => {
			return(
				currentPaletteData.push( {color: ( undefined !== this.state.value[this.state.value.active][ index ] &&  this.state.value[this.state.value.active][ index ].color ?  this.state.value[this.state.value.active][ index ].color : '' ),
				} )
			)
		} );
		const currentPaletteJson = JSON.stringify(currentPaletteData);
		return (
				<div className="cryptozfree-control-field cryptozfree-palette-control cryptozfree-color-control">
					<div className="cryptozfree-palette-header">
						<Tooltip text={ __( 'Reset Values', 'cryptozfree' ) }>
							<Button
								className="reset cryptozfree-reset"
								onClick={ () => {
									let value = this.state.value;
									const reset = JSON.parse( this.controlParams.reset );
									const newItems = this.state.value[this.state.value.active].map( ( item, thisIndex ) => {
										item = { ...item, ...reset[this.state.value.active][ thisIndex ] };
										document.documentElement.style.setProperty('--global-' + reset[this.state.value.active][ thisIndex ].slug, reset[this.state.value.active][ thisIndex ].color );						
										return item;
									} );
									value[this.state.value.active] = newItems;
									this.updateValues( value );
								} }
							>
								<Dashicon icon='image-rotate' />
							</Button>
						</Tooltip>
						{
							this.props.control.params.label &&
							<span className="customize-control-title">
								{ this.props.control.params.label }
							</span>
						}
						{
							!this.props.hideResponsive &&
							<div className="floating-controls">
								<ButtonGroup>
									{Object.keys( this.controlParams.paletteMap ).map( (palette) => {
										return (
											<Tooltip text={this.controlParams.paletteMap[palette].tooltip}>
												<Button
														isTertiary
														className={ ( palette === this.state.value.active ?
																'active-palette ' :
																'' ) + palette}
														onClick={ () => {
															this.handleChangePalette( palette );
														} }
												>
													{ this.controlParams.paletteMap[palette].tooltip }
												</Button>
											</Tooltip>
										);
									} )}
								</ButtonGroup>
							</div>
						}
					</div>
					<div className="cryptozfree-palette-colors">
						{ 'start' === this.state.fresh && (
							<Fragment>
								{ Object.keys( this.controlParams.colors ).map( ( item, index ) => {
										return (
											<ColorControl
												key={ index }
												presetColors={ this.state.colorPalette }
												color={ ( undefined !== this.state.value[this.state.value.active][ index ] &&  this.state.value[this.state.value.active][ index ].color ?  this.state.value[this.state.value.active][ index ].color : '' ) }
												isPalette={ '' }
												usePalette={ false }
												tooltip={ ( undefined !== this.controlParams.colors[ item ].tooltip ? this.controlParams.colors[ item ].tooltip : '' ) }
												onChangeComplete={ ( color, isPalette ) => this.handleChangeComplete( color, isPalette, item, index ) }
											/>
										)
								} ) }
							</Fragment>
						) }
						{ 'start' !== this.state.fresh && (
							<Fragment>
								{ Object.keys( this.controlParams.colors ).map( ( item, index ) => {
										return (
											<ColorControl
												key={ index }
												presetColors={ this.state.colorPalette }
												color={ ( undefined !== this.state.value[this.state.value.active][ index ] &&  this.state.value[this.state.value.active][ index ].color ?  this.state.value[this.state.value.active][ index ].color : '' ) }
												isPalette={ '' }
												usePalette={ false }
												tooltip={ ( undefined !== this.controlParams.colors[ item ].tooltip ? this.controlParams.colors[ item ].tooltip : '' ) }
												onChangeComplete={ ( color, isPalette ) => this.handleChangeComplete( color, isPalette, item, index ) }
											/>
										)
								} ) }
							</Fragment>
						) }
					</div>
					<div className={'cryptozfree-palette-import-wrap'}>
						<Button className={ 'cryptozfree-palette-import' } onClick={ () => { this.state.isVisible ? toggleClose() : toggleVisible() } }>
							<Dashicon icon="portfolio" />
						</Button>
						{ this.state.isVisible && (
							<Popover position="bottom right" className="cryptozfree-palette-popover-copy-paste" onClose={ toggleClose }>
								<TabPanel className="cryptozfree-palette-popover-tabs"
									activeClass="active-tab"
									initialTabName={ 'import'}
									tabs={ [
										{
											name: 'import',
											title: __( 'Select a Color Set', 'cryptozfree' ),
											className: 'cryptozfree-color-presets',
										},
										{
											name: 'custom',
											title: __( 'Export/Import', 'cryptozfree' ),
											className: 'cryptozfree-export-import',
										}
									] }>
									{
										( tab ) => {
											let tabout;
											if ( tab.name ) {
												if ( 'import' === tab.name ) {
													tabout = (
														<Fragment>
															{ Object.keys( presetPalettes ).map( ( item, index ) => {
																return (
																	<Button
																		className={ 'cryptozfree-palette-item' }
																		style={ {
																			height: '100%',
																			width: '100%',
																		} }
																		onClick={ () => this.handlePresetImport( item ) }
																		tabIndex={ 0 }
																	>
																		{ Object.keys( presetPalettes[item] ).map( ( color, subIndex ) => {
																			return (
																				<div key={ subIndex } style={ {
																					width: 30,
																					height: 30,
																					marginBottom: 0,
																					transform: 'scale(1)',
																					transition: '100ms transform ease',
																				} } className="cryptozfree-swatche-item-wrap">
																					<span
																						className={ 'cryptozfree-swatch-item' }
																						style={ {
																							height: '100%',
																							display: 'block',
																							width: '100%',
																							border: '1px solid rgb(218, 218, 218)',
																							borderRadius: '50%',
																							color: `${ presetPalettes[item][color].color }`,
																							boxShadow: `inset 0 0 0 ${ 30 / 2 }px`,
																							transition: '100ms box-shadow ease',
																						} }
																						>
																					</span>
																				</div>
																			)
																		} ) }
																	</Button>
																)
															} ) }
														</Fragment>
													);
												} else {
													tabout = (
														<Fragment>
															<h2>{ __('Export', 'cryptozfree' ) }</h2>
															<TextareaControl
																label=""
																help="Copy export data to use in another site."
																value={ currentPaletteJson }
																onChange={ false }
															/>
															<h2>{ __('Import', 'cryptozfree' ) }</h2>
															<TextareaControl
																label="Import color set from text data."
																help="Follow format from export above."
																value={ this.state.textImport }
																onChange={ ( text ) => this.setState( { textImport: text } ) }
															/>
															{ this.state.importError && (
																<p style={{color:'red'}}>{ __( 'Error with Import data', 'cryptozfree') }</p>
															) }
															<Button
																className={ 'cryptozfree-import-button' }
																isPrimary
																disabled={ this.state.textImport ? false : true }
																onClick={ () => this.handleTextImport() }
															>
																{ __('Import', 'cryptozfree' ) }
															</Button>
														</Fragment>
													);
												}
											}
											return <div>{ tabout }</div>;
										}
									}
								</TabPanel>
							</Popover>
						) }
					</div>
					{ this.props.control.params.description && (
						<span className="customize-control-description"><a href="#" target="_blank">{ this.props.control.params.description }</a></span>
					) }
				</div>
		);
	}

	updateValues( value ) {
		this.setState( { value: value } );
		this.props.control.setting.set( JSON.stringify( value ) );
		cryptozfreeCustomizerControlsData.palette = JSON.stringify( value );
	}
}

ColorComponent.propTypes = {
	control: PropTypes.object.isRequired
};

export default ColorComponent;
