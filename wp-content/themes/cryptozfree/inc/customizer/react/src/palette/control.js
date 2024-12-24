import PaletteComponent from './palette-component.js';

export const PaletteControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
		ReactDOM.render(
				<PaletteComponent control={control}/>,
				control.container[0]
		);
	}
} );
