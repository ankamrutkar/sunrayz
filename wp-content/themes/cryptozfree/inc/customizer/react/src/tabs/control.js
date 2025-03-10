import TabsComponent from './tabs-component';

export const TabsControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
		ReactDOM.render( <TabsComponent control={ control } customizer={ wp.customize } />, control.container[0] );
	}
} );
