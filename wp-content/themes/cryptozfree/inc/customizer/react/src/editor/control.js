import EditorComponent from './editor-component.js';

export const EditorControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
		ReactDOM.render(
				<EditorComponent control={control} customizer={ wp.customize } />,
				control.container[0]
		);
	}
} );
