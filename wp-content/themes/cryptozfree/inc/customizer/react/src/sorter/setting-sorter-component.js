/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import ResponsiveControl from '../common/responsive.js';
import Icons from '../common/icons.js';
import { ReactSortable } from "react-sortablejs";
import isEqual from 'lodash/isEqual';
import union from 'lodash/union';

import ItemComponent from './setting-item-component';

const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Button } = wp.components;

const { Component, Fragment } = wp.element;
class SorterComponent extends Component {
	constructor() {
		super( ...arguments );
		this.updateValues = this.updateValues.bind( this );
		this.onDragEnd = this.onDragEnd.bind( this );
		this.onDragStart = this.onDragStart.bind( this );
		this.onDragStop = this.onDragStop.bind( this );
		let value = this.props.control.settings['elements'].get();
		let baseDefault = [ 'title', 'breadcrumb', 'meta' ];
		this.defaultValue = this.props.control.params.default ? this.props.control.params.default : baseDefault;
		value = value ? union(
			value,
			this.defaultValue
		) : this.defaultValue;
		let defaultParams = {
			'group': 'title_item_group',
			'sortable': true,
			dividers: {
				dot: {
					icon: 'dot',
				},
				slash: {
					icon: 'slash',
				},
				dash: {
					icon: 'dash',
				},
				vline: {
					icon: 'vline',
				},
			},
			imageSizes: {
				thumbnail: {
					name: __( 'Thumbnail', 'cryptozfree' ),
				},
				medium: {
					name: __( 'Medium', 'cryptozfree' ),
				},
				medium_large: {
					name: __( 'Medium Large', 'cryptozfree' ),
				},
				large: {
					name: __( 'Large', 'cryptozfree' ),
				},
				full: {
					name: __( 'Full', 'cryptozfree' ),
				},
			},
			ratios: {
				'inherit': {
					'name': __( 'Inherit', 'cryptozfree' ),
				},
				'1-1': {
					'name': '1:1',
				},
				'3-4': {
					'name': '4:3',
				},
				'2-3': {
					'name': '3:2',
				},
				'9-16': {
					'name': '16:9',
				},
				'1-2': {
					'name': '2:1',
				},
				'5-4': {
					'name': '4:5',
				},
				'4-3': {
					'name': '3:4',
				},
				'3-2': {
					'name': '2:3',
				},
			}
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		this.state = {
			value: value,
		};
	}
	onDragStart() {
		var dropzones = document.querySelectorAll( '.cryptozfree-builder-area' );
		var i;
		for (i = 0; i < dropzones.length; ++i) {
			dropzones[i].classList.add( 'cryptozfree-dragging-dropzones' );
		}
	}
	onDragStop() {
		var dropzones = document.querySelectorAll( '.cryptozfree-builder-area' );
		var i;
		for (i = 0; i < dropzones.length; ++i) {
			dropzones[i].classList.remove( 'cryptozfree-dragging-dropzones' );
		}
	}
	onDragEnd( items ) {
		let updateState = this.state.value;
		let update = updateState;
		let updateItems = [];
		{ items.length > 0 && (
			items.map( ( item ) => {
				updateItems.push( item.id );
			} )
		) };
		if ( JSON.stringify( update ) !== JSON.stringify( updateItems ) ) {
			update = updateItems;
			updateState = updateItems;
			this.setState( { value: updateState } );
			this.updateValues( updateState );
		}
	}
	arraysEqual( a, b ) {
		if (a === b) return true;
		if (a == null || b == null) return false;
		if (a.length != b.length) return false;		
		for (var i = 0; i < a.length; ++i) {
			if (a[i] !== b[i]) return false;
		}
		return true;
	}
	render() {
		const controlLabel = (
			<Fragment>
				{/* <Tooltip text={ __( 'Reset Value', 'cryptozfree' ) }>
					<Button
						className="reset cryptozfree-reset"
						disabled={ ( this.state.value === this.defaultValue ) }
						onClick={ () => {
							let value = this.defaultValue;
							this.setState( { value: value } );
							this.updateValues( value );
						} }
					>
						<Dashicon icon='image-rotate' />
					</Button>
				</Tooltip> */}
				{ this.props.control.params.label &&
					this.props.control.params.label
				}
			</Fragment>
		);
		const currentList = ( typeof this.state.value != "undefined" && undefined !== this.state.value ? this.state.value : [] );
		let theItems = [];
		{ currentList.map( ( item ) => {
			theItems.push(
				{
					id: item,
				}
			)
		} ) }
		return (
			<div className="cryptozfree-control-field cryptozfree-sorter-items cryptozfree-post-title-sorter">
				<div className="cryptozfree-responsive-control-bar">
					<span className="customize-control-title">{ controlLabel }</span>
				</div>
				<div className="cryptozfree-sorter-row">
					{ this.controlParams.sortable && (
						<ReactSortable animation={100} onStart={ () => this.onDragStop() } onEnd={ () => this.onDragStop() } group={ this.controlParams.group } className={ `cryptozfree-sorter-drop cryptozfree-sorter-sortable-panel cryptozfree-meta-sorter cryptozfree-sorter-drop-${ this.controlParams.group }` } handle={ '.cryptozfree-sorter-item-panel-header' } list={ theItems } setList={ ( newState ) => this.onDragEnd( newState ) } >
							{ currentList.map( ( item, index ) => {
								return <ItemComponent
									key={ item }
									item={ item }
									setting={ this.controlParams.group }
									index={ index }
									control={ this.props.control }
									moveable={ true }
									controlParams={ this.controlParams }
									customizer={ this.props.customizer }
								/>;
							} ) }
						</ReactSortable>
					) }
					{ ! this.controlParams.sortable && (
						<div className={ `cryptozfree-sorter-drop cryptozfree-sorter-sortable-panel cryptozfree-sorter-no-sorting cryptozfree-sorter-drop-${ this.controlParams.group }` } >
							{ currentList.map( ( item, index ) => {
								return <ItemComponent
									key={ item }
									item={ item }
									setting={ this.controlParams.group }
									index={ index }
									moveable={ false }
									control={ this.props.control }
									controlParams={ this.controlParams }
									customizer={ this.props.customizer }
								/>;
							} ) }
						</div>
					) }
				</div>
			</div>
		);
	}
	updateValues( value ) {
		this.props.control.settings['elements'].set( value );
	}
}

SorterComponent.propTypes = {
	control: PropTypes.object.isRequired,
	customizer: PropTypes.object.isRequired
};

export default SorterComponent;
