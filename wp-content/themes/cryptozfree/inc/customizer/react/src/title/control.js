import TitleComponent from './title-component.js';

export const TitleControl = wp.customize.cryptozfreeControl.extend( {
	renderContent: function renderContent() {
		let control = this;
		ReactDOM.render(
				<TitleComponent control={control} />,
				control.container[0]
		);
	}
} );
