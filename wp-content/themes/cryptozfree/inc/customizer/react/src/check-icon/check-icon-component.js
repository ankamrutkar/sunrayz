/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Icons from '../common/icons.js';

const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Button } = wp.components;

const { Component, Fragment } = wp.element;
class CheckIconComponent extends Component {
	constructor() {
		super( ...arguments );
		this.updateValues = this.updateValues.bind( this );
		let value = this.props.control.setting.get();
		let defaultParams = {
			options: {
				desktop: {
					name: __( 'Desktop', 'cryptozfree' ),
					icon: 'desktop',
				},
				tablet: {
					name: __( 'Tablet', 'cryptozfree' ),
					icon: 'tablet',
				},
				mobile: {
					name: __( 'Mobile', 'cryptozfree' ),
					icon: 'smartphone',
				},
			},
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		let baseDefault = {
			'mobile': true,
			'tablet': true,
			'desktop': true,
		};
		this.defaultValue = this.props.control.params.default ? this.props.control.params.default : baseDefault;
		value = value ? {
			...JSON.parse( JSON.stringify( this.defaultValue ) ),
			...value
		} : JSON.parse( JSON.stringify( this.defaultValue ) );
		this.state = {
			value: value,
		};
	}
	render() {
		const controlLabel = (
			<Fragment>
				<Tooltip text={ __( 'Reset Values', 'cryptozfree' ) }>
					<Button
						className="reset cryptozfree-reset"
						disabled={ ( this.state.value == this.defaultValue ) }
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
		return (
			<div className="cryptozfree-control-field cryptozfree-radio-icon-control">
				<div className="cryptozfree-responsive-control-bar">
					<span className="customize-control-title">{ controlLabel }</span>
				</div>
				<ButtonGroup className="cryptozfree-radio-container-control">
					{ Object.keys( this.controlParams.options ).map( ( item ) => {
						return (
							<Fragment>
								<Tooltip text={ this.controlParams.options[ item ].name }>
									<Button
										isTertiary
										className={ ( true === this.state.value[ item ] ?
												'active-radio ' :
												'' ) + item }
										onClick={ () => {
											let value = this.state.value;
											if( value[ item ] ) {
												value[ item ] = false;
											} else {
												value[ item ] = true;
											}
											this.setState( { value: value });
											this.updateValues( value );
										} }
									>
										{ this.controlParams.options[ item ].icon && (
											<span className="cryptozfree-radio-icon">
												{ <Dashicon icon={this.controlParams.options[ item ].icon}/> }
											</span>
										) }
										{ ! this.controlParams.options[ item ].icon && (
											<span className="cryptozfree-radio-name">
												{ this.controlParams.options[ item ].name }
											</span>
										) }
									</Button>
								</Tooltip>
							</Fragment>
						);
					} )}
				</ButtonGroup>
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

CheckIconComponent.propTypes = {
	control: PropTypes.object.isRequired
};

export default CheckIconComponent;
