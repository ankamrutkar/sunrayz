import AvailableComponent from './available-component.js';

export const AvailableControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
	ReactDOM.render( <AvailableComponent control={ control } customizer={ wp.customize } />, control.container[0] );
	}
} );
