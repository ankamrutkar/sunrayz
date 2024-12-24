/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';

import ResponsiveControl from '../common/responsive.js';
import Icons from '../common/icons.js';

const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Button } = wp.components;

const { Component, Fragment } = wp.element;
class RadioIconComponent extends Component {
	constructor() {
		super( ...arguments );
		this.updateValues = this.updateValues.bind( this );
		let value = this.props.control.setting.get();
		let baseDefault = {
			'include': {
				'mobile': '',
				'tablet': '',
				'desktop': 'logo_title_tagline'
			},
			'layout': {
				'mobile': '',
				'tablet': '',
				'desktop': 'standard'
			}
		};
		this.defaultValue = this.props.control.params.default ? {
			...baseDefault,
			...this.props.control.params.default
		} : baseDefault;
		value = value ? {
			...this.defaultValue,
			...value
		} : this.defaultValue;
		let defaultParams = {
			include: {
				logo_only: {
					tooltip: __( 'Logo Only', 'cryptozfree' ),
					name: __( 'Logo', 'cryptozfree' ),
				},
				logo_title: {
					tooltip: __( 'Logo & Title', 'cryptozfree' ),
					name: __( 'Logo & Title', 'cryptozfree' ),
				},
				logo_title_tagline: {
					tooltip: __( 'Logo, Title & Tagline', 'cryptozfree' ),
					name: __( 'Logo, Title & Tagline', 'cryptozfree' ),
				},
			},
			layout: {
				logo_only: {
					standard: {
						tooltip: __( 'Logo Only', 'cryptozfree' ),
						icon: Icons.logo
					},
				},
				logo_title: {
					standard: {
						tooltip: __( 'Logo - Title', 'cryptozfree' ),
						icon: Icons.logoTitle
					},
					title_logo: {
						tooltip: __( 'Title - Logo', 'cryptozfree' ),
						icon: Icons.titleLogo
					},
					top_logo_title: {
						tooltip: __( 'Top Logo - Title', 'cryptozfree' ),
						icon: Icons.topLogoTitle
					},
					top_title_logo: {
						tooltip: __( 'Top Title - Logo', 'cryptozfree' ),
						icon: Icons.topTitleLogo
					},
				},
				logo_title_tagline: {
					standard: {
						tooltip: __( 'Logo - Title - Tagline', 'cryptozfree' ),
						icon: Icons.logoTitleTag
					},
					title_tag_logo: {
						tooltip: __( 'Title - Tagline - Logo', 'cryptozfree' ),
						icon: Icons.titleTagLogo
					},
					top_logo_title_tag: {
						tooltip: __( 'Top Logo - Title - Tagline', 'cryptozfree' ),
						icon: Icons.topLogoTitleTag
					},
					top_title_tag_logo: {
						tooltip: __( 'Top Title - Tagline - Logo', 'cryptozfree' ),
						icon: Icons.topTitleTagLogo
					},
					top_title_logo_tag: {
						tooltip: __( 'Top Title - Logo - Tagline', 'cryptozfree' ),
						icon: Icons.topTitleLogoTag
					}
				},
			}
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		this.state = {
			currentDevice: 'desktop',
			include: value.include,
			layout: value.layout,
		};
	}
	render() {
		const controlLabel = (
			<Fragment>
				{ this.state.currentDevice !== 'desktop' && (
					<Tooltip text={ __( 'Reset Device Values', 'cryptozfree' ) }>
						<Button
							className="reset cryptozfree-reset"
							disabled={ ( this.state.include[this.state.currentDevice] === this.defaultValue.include[this.state.currentDevice] ) && ( this.state.layout[this.state.currentDevice] === this.defaultValue.layout[this.state.currentDevice] ) }
							onClick={ () => {
								let value = this.state.include;
								value[this.state.currentDevice] = this.defaultValue.include[this.state.currentDevice];
								let svalue = this.state.layout;
								svalue[this.state.currentDevice] = this.defaultValue.layout[this.state.currentDevice];
								this.setState( { include: value, layout: svalue } );
								this.updateValues( { include: value, layout: svalue } );
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
		return (
			<div className="cryptozfree-control-field cryptozfree-radio-icon-control">
				<ResponsiveControl
					onChange={ ( currentDevice) => this.setState( { currentDevice } ) }
					controlLabel={ controlLabel }
				>
					<ButtonGroup className="cryptozfree-radio-container-control">
						{ Object.keys( this.controlParams.include ).map( ( item ) => {
							return (
								<Tooltip text={ this.controlParams.include[ item ].tooltip }>
									<Button
											isTertiary
											className={ ( item === this.state.include[this.state.currentDevice] ?
													'active-radio ' :
													'' ) + item }
											onClick={ () => {
												let value = this.state.include;
												value[ this.state.currentDevice ] = item;
												let svalue = this.state.layout;
												svalue[ this.state.currentDevice ] = 'standard';
												this.setState( { include: value, layout: svalue } );
												this.updateValues( { include: value, layout: svalue } );
											} }
									>
										{ this.controlParams.include[ item ].name }
									</Button>
								</Tooltip>
							);
						} )}
					</ButtonGroup>
					{ undefined !== this.state.include[this.state.currentDevice] && '' !== this.state.include[this.state.currentDevice] && 'logo_only' !== this.state.include[this.state.currentDevice] && (
						<ButtonGroup className="cryptozfree-radio-container-control cryptozfree-radio-icon-container-control">
							{ Object.keys( this.controlParams.layout[this.state.include[this.state.currentDevice] ] ).map( ( item ) => {
								return (
									<Tooltip text={ this.controlParams.layout[this.state.include[this.state.currentDevice] ][ item ].tooltip }>
										<Button
												isTertiary
												className={ ( item === this.state.layout[this.state.currentDevice] ?
														'active-radio ' :
														'' ) + item }
												onClick={ () => {
													let value = this.state.layout;
													value[ this.state.currentDevice ] = item;
													this.setState( { layout: value } );
													this.updateValues( { layout: value } );
												} }
										>
											{ this.controlParams.layout[this.state.include[this.state.currentDevice] ][ item ].icon }
										</Button>
									</Tooltip>
								);
							} )}
						</ButtonGroup>
					) }
				</ResponsiveControl>
			</div>
		);
	}

	updateValues( value ) {
		this.props.control.setting.set( {
			...this.props.control.setting.get(),
			...value,
			flag: !this.props.control.setting.get().flag
		} );
	}
}

RadioIconComponent.propTypes = {
	control: PropTypes.object.isRequired
};

export default RadioIconComponent;
