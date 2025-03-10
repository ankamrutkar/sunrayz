import ColorLinkComponent from './color-link-component.js';

export const ColorLinkControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
		ReactDOM.render(
			<ColorLinkComponent control={control} customizer={ wp.customize }/>,
			control.container[0]
		);
	}
} );
