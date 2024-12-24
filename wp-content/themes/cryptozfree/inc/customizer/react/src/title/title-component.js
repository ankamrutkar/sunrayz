import PropTypes from 'prop-types';

const { __ } = wp.i18n;
const { Component } = wp.element;
const { ToggleControl } = wp.components;

class TitleComponent extends Component {
	constructor(props) {
		super( props );
	}

	render() {
		return (
				<div className="cryptozfree-control-field cryptozfree-title-control">
					{ this.props.control.params.label && (
						<span class="customize-control-title">
							{ this.props.control.params.label }
						</span>
					) }
					{ this.props.control.params.description && (
						<span class="customize-control-description">
							{ this.props.control.params.description }
						</span>
					) }
				</div>
		);
	}
}

TitleComponent.propTypes = {
	control: PropTypes.object.isRequired
};

export default TitleComponent;
