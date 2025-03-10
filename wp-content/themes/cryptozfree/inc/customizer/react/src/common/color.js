import PropTypes from 'prop-types';
import SwatchesControl from './swatches';
import cryptozfreeColorPicker from './color-picker';
import Icons from './icons';
const { __ } = wp.i18n;
const {
	Component,
	Fragment
} = wp.element;
const {
	Button,
	Popover,
	Dashicon,
	ColorIndicator,
	Tooltip,
	Icon,
	TabPanel,
	__experimentalGradientPicker,
} = wp.components;

class ColorControl extends Component {
	constructor(props) {
		super( props );
		this.onChangeState = this.onChangeState.bind( this );
		this.onChangeComplete = this.onChangeComplete.bind( this );
		this.onChangeGradientComplete = this.onChangeGradientComplete.bind( this );
		this.state = {
			isVisible: false,
			refresh: false,
			stateColor: this.props.color,
			color: this.props.color,
			isPalette: ( this.props.color && this.props.color.includes( 'palette' ) ? true : false ),
			palette: ( this.props.presetColors && this.props.presetColors ? this.props.presetColors : [] ),
			activePalette: ( this.props.presetColors && this.props.presetColors.active ? this.props.presetColors.active : 'palette' ),
			supportGradient: ( undefined === __experimentalGradientPicker ? false : true ),
		};
	}

	render() {
		//console.log(this.props.presetColors );
		const toggleVisible = () => {
			if ( this.props.usePalette ) {
				const updateColors = JSON.parse( this.props.customizer.control( 'cryptozfree_color_palette' ).setting.get() );
				const active = ( updateColors && updateColors.active ? updateColors.active : 'palette' );
				this.setState( { palette: updateColors, activePalette: active } );
			}
			if ( this.state.refresh === true ) {
				this.setState( { refresh: false } );
			} else {
				this.setState( { refresh: true } );
			}
			this.setState( { isVisible: true } );
		};
		const toggleClose = () => {
			if ( this.state.isVisible === true ) {
				this.setState( { isVisible: false } );
			}
		};
		const styleing = {
			saturation: {
				paddingBottom: '50%',
				width: '100%',
				position: 'relative',
				overflow: 'hidden',
			},
		};
		const position = ( this.props.position ? this.props.position : "top right" );
		const showingGradient = ( this.props.allowGradient && this.state.supportGradient ? true : false );
		return (
			<div className="cryptozfree-color-picker-wrap">
				{ this.props.colorDefault && this.props.color && this.props.color !== this.props.colorDefault && (
					<Tooltip text={ __( 'Clear' ) }>
						<span className="tooltip-clear">
							<Button
								className="components-color-palette__clear"
								type="button"
								onClick={ () => {
									this.setState( { color: this.props.colorDefault, isPalette: '' } );
									this.props.onChangeComplete( '', '' );
								} }
								isSmall
							>
								<Dashicon icon="redo" />
							</Button>
						</span>
					</Tooltip>
				) }
				{ showingGradient && (
					<Fragment>
						{ this.state.isVisible && (
							<Popover position={ position } className="cryptozfree-popover-color" onClose={ toggleClose }>
								<TabPanel className="cryptozfree-popover-tabs cryptozfree-background-tabs"
									activeClass="active-tab"
									initialTabName={ ( this.state.color && this.state.color.includes( 'gradient' ) ? 'gradient' : 'color' ) }
									tabs={ [
										{
											name: 'color',
											title: __( 'Color', 'cryptozfree' ),
											className: 'cryptozfree-color-background',
										},
										{
											name: 'gradient',
											title: __( 'Gradient', 'cryptozfree' ),
											className: 'cryptozfree-gradient-background',
										}
									] }>
									{
										( tab ) => {
											let tabout;
											if ( tab.name ) {
												if ( 'gradient' === tab.name ) {
													tabout = (
														<Fragment>
															<__experimentalGradientPicker
																value={ this.state.color && this.state.color.includes( 'gradient' ) ? this.state.color : '' }
																onChange={ ( gradient ) => this.onChangeGradientComplete( gradient ) }
															/>
														</Fragment>
													);
												} else {
													tabout = (
														<Fragment>
															{ this.state.refresh && (
																<Fragment>
																	<cryptozfreeColorPicker
																		color={ ( this.state.isPalette && this.state.palette.palette && this.state.palette.palette[parseInt(this.state.color.slice(-1), 10 ) - 1] ? this.state.palette.palette[parseInt(this.state.color.slice(-1), 10 ) - 1 ].color : this.state.color ) }
																		onChange={ ( color ) => this.onChangeState( color, '' ) }
																		onChangeComplete={ ( color ) => this.onChangeComplete( color, '' ) }
																	/>
																	{ this.props.usePalette && (
																		<SwatchesControl
																			colors={ ( this.state.palette && this.state.palette[ this.state.activePalette ] ? this.state.palette[ this.state.activePalette ] : [] ) }
																			isPalette={ ( this.state.isPalette ? this.state.color : '' ) }
																			onClick={ ( color, palette ) => this.onChangeComplete( color, palette ) }
																		/>
																	) }
																</Fragment>
															) }
															{ ! this.state.refresh &&  (
																<Fragment>
																	<cryptozfreeColorPicker
																		//presetColors={ [] }
																		color={ ( this.state.isPalette && this.state.palette[ this.state.activePalette ] && this.state.palette[ this.state.activePalette ][parseInt(this.state.color.slice(-1), 10 ) - 1] ? this.state.palette[ this.state.activePalette ][parseInt(this.state.color.slice(-1), 10 ) - 1 ].color : this.state.color ) }
																		onChange={ ( color ) => this.onChangeState( color, '' ) }
																		onChangeComplete={ ( color ) => this.onChangeComplete( color, '' ) }
																		//width={ 300 }
																		//styles={ styleing }
																	/>
																	{ this.props.usePalette && (
																		<SwatchesControl
																			colors={ ( this.state.palette && this.state.palette[ this.state.activePalette ] ? this.state.palette[ this.state.activePalette ] : [] ) }
																			isPalette={ ( this.state.isPalette ? this.state.color : '' ) }
																			onClick={ ( color, palette ) => this.onChangeComplete( color, palette ) }
																		/>
																	) }
																</Fragment>
															) }
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
					</Fragment>
				) }
				{ ! showingGradient && (
					<Fragment>
						{ this.state.isVisible && this.state.refresh && (
							<Popover position="top left" className="cryptozfree-popover-color" onClose={ toggleClose }>
								<cryptozfreeColorPicker
									color={ ( this.state.isPalette && this.state.palette.palette && this.state.palette.palette[parseInt(this.state.color.slice(-1), 10 ) - 1] ? this.state.palette.palette[parseInt(this.state.color.slice(-1), 10 ) - 1 ].color : this.state.color ) }
									onChange={ ( color ) => this.onChangeState( color, '' ) }
									onChangeComplete={ ( color ) => this.onChangeComplete( color, '' ) }
								/>
								{ this.props.usePalette && (
									<SwatchesControl
										colors={ ( this.state.palette && this.state.palette[ this.state.activePalette ] ? this.state.palette[ this.state.activePalette ] : [] ) }
										isPalette={ ( this.state.isPalette ? this.state.color : '' ) }
										onClick={ ( color, palette ) => this.onChangeComplete( color, palette ) }
									/>
								) }
							</Popover>
						) }
						{ this.state.isVisible && ! this.state.refresh &&  (
							<Popover position="top left" className="cryptozfree-popover-color" onClose={ toggleClose }>
								<cryptozfreeColorPicker
									//presetColors={ [] }
									color={ ( this.state.isPalette && this.state.palette[ this.state.activePalette ] && this.state.palette[ this.state.activePalette ][parseInt(this.state.color.slice(-1), 10 ) - 1] ? this.state.palette[ this.state.activePalette ][parseInt(this.state.color.slice(-1), 10 ) - 1 ].color : this.state.color ) }
									onChange={ ( color ) => this.onChangeState( color, '' ) }
									onChangeComplete={ ( color ) => this.onChangeComplete( color, '' ) }
									//width={ 300 }
									//styles={ styleing }
								/>
								{ this.props.usePalette && (
									<SwatchesControl
										colors={ ( this.state.palette && this.state.palette[ this.state.activePalette ] ? this.state.palette[ this.state.activePalette ] : [] ) }
										isPalette={ ( this.state.isPalette ? this.state.color : '' ) }
										onClick={ ( color, palette ) => this.onChangeComplete( color, palette ) }
									/>
								) }
							</Popover>
						) }
					</Fragment>
				) }
				<Tooltip text={ this.props.tooltip ? this.props.tooltip : __( 'Select Color', 'cryptozfree' ) }>
					<div className="color-button-wrap">
						<Button className={ 'cryptozfree-color-icon-indicate' } onClick={ () => { this.state.isVisible ? toggleClose() : toggleVisible() } }>
							<ColorIndicator className="cryptozfree-advanced-color-indicate" colorValue={ ( this.state.isPalette ? 'var(--global-' + this.props.color + ')' : this.props.color ) } />
							{ this.state.isPalette && (
								<Icon className="dashicon" icon={ Icons.globe }/>
							) }
						</Button>
					</div>
				</Tooltip>
			</div>
		);
	}

	onChangeState( color, palette ) {
		let newColor;
		if ( palette ) {
			newColor = palette;
		} else if ( undefined !== color.rgb && undefined !== color.rgb.a && 1 !== color.rgb.a ) {
			newColor = 'rgba(' +  color.rgb.r + ',' +  color.rgb.g + ',' +  color.rgb.b + ',' + color.rgb.a + ')';
		} else {
			newColor = color.hex;
		}
		this.setState( { color: newColor, isPalette: ( palette ? true : false ) } );
		if ( undefined !== this.props.onChange ) {
			this.props.onChange( color, palette );
		}
	}
	onChangeGradientComplete( gradient ) {
		let newColor;
		if ( undefined === gradient ) {
			newColor = '';
		} else {
			newColor = gradient;
		}
		this.setState( { color: newColor, isPalette: false } );
		this.props.onChangeComplete( newColor, '' );
	}
	onChangeComplete( color, palette ) {
		let newColor;
		if ( palette ) {
			newColor = palette;
		} else if ( undefined !== color.rgb && undefined !== color.rgb.a && 1 !== color.rgb.a ) {
			newColor = 'rgba(' +  color.rgb.r + ',' +  color.rgb.g + ',' +  color.rgb.b + ',' + color.rgb.a + ')';
		} else {
			newColor = color.hex;
		}
		this.setState( { color: newColor, isPalette: ( palette ? true : false ) } );
		this.props.onChangeComplete( color, palette );
	}

}

ColorControl.propTypes = {
	color: PropTypes.string,
	usePalette: PropTypes.bool,
	palette: PropTypes.string,
	presetColors: PropTypes.array,
	onChangeComplete: PropTypes.func,
	onChange: PropTypes.func,
	customizer: PropTypes.object
};

export default ColorControl;
