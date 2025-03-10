/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';

import ResponsiveControl from '../common/responsive.js';
import Icons from '../common/icons.js';

const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Button } = wp.components;

const { Component, Fragment } = wp.element;
class RowLayoutComponent extends Component {
	constructor() {
		super( ...arguments );
		this.updateValues = this.updateValues.bind( this );
		this.onFooterUpdate = this.onFooterUpdate.bind( this );
		this.linkColumns();
		let value = this.props.control.setting.get();
		let defaultParams = {
			desktop: {
				'column5': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'fivecol',
					},
				},
				'column4': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'fourcol',
					},
					'left-forty': {
						tooltip: __( 'Left Heavy 40/20/20/20', 'cryptozfree' ),
						icon: 'lfourforty',
					},
					'right-forty': {
						tooltip: __( 'Right Heavy 20/20/20/40', 'cryptozfree' ),
						icon: 'rfourforty',
					},
				},
				'column3': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'threecol',
					},
					'left-half': {
						tooltip: __( 'Left Heavy 50/25/25', 'cryptozfree' ),
						icon: 'lefthalf',
					},
					'right-half': {
						tooltip: __( 'Right Heavy 25/25/50', 'cryptozfree' ),
						icon: 'righthalf',
					},
					'center-half': {
						tooltip: __( 'Center Heavy 25/50/25', 'cryptozfree' ),
						icon: 'centerhalf',
					},
					'center-wide': {
						tooltip: __( 'Wide Center 20/60/20', 'cryptozfree' ),
						icon: 'widecenter',
					},
				},
				'column2': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'twocol',
					},
					'left-golden': {
						tooltip: __( 'Left Heavy 66/33', 'cryptozfree' ),
						icon: 'twoleftgolden',
					},
					'right-golden': {
						tooltip: __( 'Right Heavy 33/66', 'cryptozfree' ),
						icon: 'tworightgolden',
					},
				},
				'column1': {
					'row': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'row',
					},
				}
			},
			mobile: {
				'column5': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'fivecol',
					},
					'row': {
						tooltip: __( 'Collapse to Rows', 'cryptozfree' ),
						icon: 'collapserowfive',
					},
				},
				'column4': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'fourcol',
					},
					'two-grid': {
						tooltip: __( 'Two Column Grid', 'cryptozfree' ),
						icon: 'grid',
					},
					'row': {
						tooltip: __( 'Collapse to Rows', 'cryptozfree' ),
						icon: 'collapserowfour',
					},
				},
				'column3': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'threecol',
					},
					'left-half': {
						tooltip: __( 'Left Heavy 50/25/25', 'cryptozfree' ),
						icon: 'lefthalf',
					},
					'right-half': {
						tooltip: __( 'Right Heavy 25/25/50', 'cryptozfree' ),
						icon: 'righthalf',
					},
					'center-half': {
						tooltip: __( 'Center Heavy 25/50/25', 'cryptozfree' ),
						icon: 'centerhalf',
					},
					'center-wide': {
						tooltip: __( 'Wide Center 20/60/20', 'cryptozfree' ),
						icon: 'widecenter',
					},
					'first-row': {
						tooltip: __( 'First Row, Next Columns 100 - 50/50', 'cryptozfree' ),
						icon: 'firstrow',
					},
					'last-row': {
						tooltip: __( 'Last Row, Previous Columns 50/50 - 100', 'cryptozfree' ),
						icon: 'lastrow',
					},
					'row': {
						tooltip: __( 'Collapse to Rows', 'cryptozfree' ),
						icon: 'collapserowthree',
					},
				},
				'column2': {
					'equal': {
						tooltip: __( 'Equal Width Columns', 'cryptozfree' ),
						icon: 'twocol',
					},
					'left-golden': {
						tooltip: __( 'Left Heavy 66/33', 'cryptozfree' ),
						icon: 'twoleftgolden',
					},
					'right-golden': {
						tooltip: __( 'Right Heavy 33/66', 'cryptozfree' ),
						icon: 'tworightgolden',
					},
					'row': {
						tooltip: __( 'Collapse to Rows', 'cryptozfree' ),
						icon: 'collapserow',
					},
				},
				'column1': {
					'row': {
						tooltip: __( 'Single Row', 'cryptozfree' ),
						icon: 'row',
					},
				}
			},
			responsive: true,
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		let responsiveDefault = {
			'mobile': 'row',
			'tablet': '',
			'desktop': 'equal'
		};
		let nonResponsiveDefault = 'equal';
		let baseDefault;
		if ( this.controlParams.responsive ) {
			baseDefault = responsiveDefault;
			this.defaultValue = this.props.control.params.default ? {
				...baseDefault,
				...this.props.control.params.default
			} : baseDefault;
		} else {
			baseDefault = nonResponsiveDefault;
			this.defaultValue = this.props.control.params.default ? this.props.control.params.default : baseDefault;
		}
		if ( this.controlParams.responsive ) {
			value = value ? {
				...JSON.parse( JSON.stringify( this.defaultValue ) ),
				...value
			} : JSON.parse( JSON.stringify( this.defaultValue ) );
		} else {
			value = value ? value : this.defaultValue;
		}
		let columns = 0;
		if ( this.controlParams.footer ) {
			columns = parseInt( this.props.customizer.control( 'footer_' + this.controlParams.footer + '_columns' ).setting.get(), 10 );
		}
		this.state = {
			currentDevice: 'desktop',
			columns: columns,
			value: value,
		};
	}
	render() {
		const responsiveControlLabel = (
			<Fragment>
				{ this.state.currentDevice !== 'desktop' && (
					<Tooltip text={ __( 'Reset Device Values', 'cryptozfree' ) }>
						<Button
							className="reset cryptozfree-reset"
							disabled={ ( this.state.value[this.state.currentDevice] === this.defaultValue[this.state.currentDevice] ) }
							onClick={ () => {
								let value = this.state.value;
								value[this.state.currentDevice] = this.defaultValue[this.state.currentDevice];
								this.setState( value );
								this.updateValues( value );
							} }
						>
							<Dashicon icon='image-rotate' />
						</Button>
					</Tooltip>
				) }
				{ this.props.control.params.label &&
					this.props.control.params.label
				}
			</Fragment>
		);
		const controlLabel = (
			<Fragment>
				<Tooltip text={ __( 'Reset Values', 'cryptozfree' ) }>
					<Button
						className="reset cryptozfree-reset"
						disabled={ ( this.state.value === this.defaultValue ) }
						onClick={ () => {
							let value = this.defaultValue;
							this.setState( { value: this.defaultValue } );
							this.updateValues( value );
						} }
					>
						<Dashicon icon='image-rotate' />
					</Button>
				</Tooltip>
				{ this.props.control.params.label &&
					this.props.control.params.label
				}
			</Fragment>
		);
		let controlMap = {}
		if ( this.state.currentDevice !== 'desktop' ) {
			controlMap = this.controlParams.mobile['column' + this.state.columns ];
		} else {
			controlMap = this.controlParams.desktop['column' + this.state.columns ];
		}
		return (
			<div className="cryptozfree-control-field cryptozfree-radio-icon-control cryptozfree-row-layout-control">
				{ this.controlParams.responsive && (
					<ResponsiveControl
						onChange={ ( currentDevice) => this.setState( { currentDevice } ) }
						controlLabel={ responsiveControlLabel }
					>
						<ButtonGroup className="cryptozfree-radio-container-control">
							{ Object.keys( controlMap ).map( ( item ) => {
								return (
									<Fragment>
										{ controlMap[ item ].tooltip && (
											<Tooltip text={ controlMap[ item ].tooltip }>
												<Button
													isTertiary
													className={ ( item === this.state.value[this.state.currentDevice] ?
															'active-radio ' :
															'' ) + 'cryptozfree-btn-item-' + item }
													onClick={ () => {
														let value = this.state.value;
														value[ this.state.currentDevice ] = item;
														this.setState( value );
														this.updateValues( value );
													} }
												>
													{ controlMap[ item ].icon && (
														<span className="cryptozfree-radio-icon">
															{ Icons[ controlMap[ item ].icon ] }
														</span>
													) }
													{ controlMap[ item ].dashicon && (
														<span className="cryptozfree-radio-icon cryptozfree-radio-dashicon">
															<Dashicon icon={ controlMap[ item ].dashicon } />
														</span>
													) }
													{ controlMap[ item ].name && (
															controlMap[ item ].name
													) }
												</Button>
											</Tooltip>
										) }
										{ ! controlMap[ item ].tooltip && (
											<Button
													isTertiary
													className={ ( item === this.state.value[this.state.currentDevice] ?
															'active-radio ' :
															'' ) + 'cryptozfree-btn-item-' + item }
															onClick={ () => {
																let value = this.state.value;
																value[ this.state.currentDevice ] = item;
																this.setState( value );
																this.updateValues( value );
															} }
											>
												{ controlMap[ item ].icon && (
														<span className="cryptozfree-radio-icon">
															{ Icons[ controlMap[ item ].icon ] }
														</span>
													) }
													{ controlMap[ item ].dashicon && (
														<span className="cryptozfree-radio-icon cryptozfree-radio-dashicon">
															<Dashicon icon={ controlMap[ item ].dashicon } />
														</span>
													) }
													{ controlMap[ item ].name && (
															controlMap[ item ].name
													) }
											</Button>
										) }
									</Fragment>
								);
							} )}
						</ButtonGroup>
					</ResponsiveControl>
				) }
				{ ! this.controlParams.responsive && (
					<Fragment>
						<div className="cryptozfree-responsive-control-bar">
							<span className="customize-control-title">{ controlLabel }</span>
						</div>
						<ButtonGroup className="cryptozfree-radio-container-control">
							{ Object.keys( controlMap ).map( ( item ) => {
								return (
									<Fragment>
										{ controlMap[ item ].tooltip && (
											<Tooltip text={ controlMap[ item ].tooltip }>
												<Button
													isTertiary
													className={ ( item === this.state.value ?
															'active-radio ' :
															'' ) + 'cryptozfree-btn-item-' + item }
													onClick={ () => {
														let value = this.state.value;
														value = item;
														this.setState( { value: item });
														this.updateValues( value );
													} }
												>
													{ controlMap[ item ].icon && (
														<span className="cryptozfree-radio-icon">
															{ Icons[ controlMap[ item ].icon ] }
														</span>
													) }
													{ controlMap[ item ].name && (
															controlMap[ item ].name
													) }
												</Button>
											</Tooltip>
										) }
										{ ! controlMap[ item ].tooltip && (
											<Button
													isTertiary
													className={ ( item === this.state.value ?
															'active-radio ' :
															'' ) + 'cryptozfree-btn-item-' + item }
															onClick={ () => {
																let value = this.state.value;
																value = item;
																this.setState( { value: item });
																this.updateValues( value );
															} }
											>
												{ controlMap[ item ].icon && (
														<span className="cryptozfree-radio-icon">
															{ Icons[ controlMap[ item ].icon ] }
														</span>
													) }
													{ controlMap[ item ].name && (
															controlMap[ item ].name
													) }
											</Button>
										) }
									</Fragment>
								);
							} )}
						</ButtonGroup>
					</Fragment>
				) }
			</div>
		);
	}
	onFooterUpdate() {
		const columns = parseInt( this.props.customizer.control( 'footer_' + this.controlParams.footer + '_columns' ).setting.get(), 10 );
		if ( this.state.columns !== columns ) {
			this.setState( { columns: columns } );
			let value = this.state.value;
			if ( columns === 1 ) {
				value.desktop = 'row';
			} else {
				value.desktop = 'equal';
			}
			value.tablet  = '';
			value.mobile  = 'row';
			this.setState( value );
			this.updateValues( value );
		}
	}
	linkColumns() {
		let self = this;
		document.addEventListener( 'cryptozfreeUpdateFooterColumns', function( e ) {
			if ( e.detail === self.controlParams.footer ) {
				setTimeout(function(){ self.onFooterUpdate(); }, 200);
			}
		} );
	}
	updateValues( value ) {
		if ( undefined !== this.controlParams.footer && this.controlParams.footer ) {
			let event = new CustomEvent(
				'cryptozfreeUpdateFooterColumns', {
					'detail': this.controlParams.footer,
				} );
			document.dispatchEvent( event );
		}
		if ( this.controlParams.responsive ) {
			this.props.control.setting.set( {
			...this.props.control.setting.get(),
			...value,
			flag: !this.props.control.setting.get().flag
		} );
		} else {
			this.props.control.setting.set( value );
		}
	}
}

RowLayoutComponent.propTypes = {
	control: PropTypes.object.isRequired
};

export default RowLayoutComponent;
