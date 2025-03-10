/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import ResponsiveControl from '../common/responsive.js';
import Icons from '../common/icons.js';
import { ReactSortable } from "react-sortablejs";
const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Button } = wp.components;

const { Component, Fragment } = wp.element;
class FocusButtonComponent extends Component {
	constructor() {
		super( ...arguments );
		this.focusPanel = this.focusPanel.bind( this );
		let defaultParams = {
			'section': '',

		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
	}
	focusPanel( section ) {
		if ( undefined !== this.props.customizer.section( section ) ) {
			this.props.customizer.section( section ).focus();
		}
	}
	render() {
		return (
			<div className="cryptozfree-control-field cryptozfree-available-items">
				<div className={ 'cryptozfree-builder-item-start' }>
					<Button className="cryptozfree-builder-item" onClick={ () => this.focusPanel( this.controlParams.section ) } data-section={ this.controlParams.section }>
						{ ( this.props.control.params.label ? this.props.control.params.label : '' ) }
						<span
							className="cryptozfree-builder-item-icon"
						>
							<Dashicon icon="arrow-right-alt2"/>
						</span>
					</Button>
				</div>
			</div>
		);
	}
}

FocusButtonComponent.propTypes = {
	control: PropTypes.object.isRequired,
	customizer: PropTypes.object.isRequired
};

export default FocusButtonComponent;
