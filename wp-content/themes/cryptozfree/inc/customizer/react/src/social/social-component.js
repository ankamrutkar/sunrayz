/* jshint esversion: 6 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import ResponsiveControl from '../common/responsive.js';
import Icons from '../common/icons.js';
import { ReactSortable } from "react-sortablejs";
import uniqueId from 'lodash/uniqueId';

import ItemComponent from './item-component';

const { __ } = wp.i18n;

const { ButtonGroup, Dashicon, Tooltip, Popover, Button, SelectControl } = wp.components;

const { Component, Fragment } = wp.element;
class SocialComponent extends Component {
	constructor() {
		super( ...arguments );
		this.updateValues = this.updateValues.bind( this );
		this.onDragEnd = this.onDragEnd.bind( this );
		this.onDragStart = this.onDragStart.bind( this );
		this.onDragStop = this.onDragStop.bind( this );
		this.removeItem = this.removeItem.bind( this );
		this.saveArrayUpdate = this.saveArrayUpdate.bind( this );
		this.toggleEnableItem = this.toggleEnableItem.bind( this );
		this.onChangeIcon = this.onChangeIcon.bind( this );
		this.onChangeLabel = this.onChangeLabel.bind( this );
		this.onChangeURL = this.onChangeURL.bind( this );
		this.onChangeAttachment = this.onChangeAttachment.bind( this );
		this.onChangeWidth = this.onChangeWidth.bind( this );
		this.onChangeSource = this.onChangeSource.bind( this );
		this.addItem = this.addItem.bind( this );
		let value = this.props.control.setting.get();
		let baseDefault = {
			'items': [
				{
					'id': 'facebook',
					'enabled': true,
					'source': 'icon',
					'url': '',
					'imageid': '',
					'width': 24,
					'icon': 'facebook',
					'label': 'Facebook',
				},
				{
					'id': 'twitter',
					'enabled': true,
					'source': 'icon',
					'url': '',
					'imageid': '',
					'width': 24,
					'icon': 'twitter',
					'label': 'Twitter',
				}
			],
		};
		this.defaultValue = this.props.control.params.default ? {
			...baseDefault,
			...this.props.control.params.default
		} : baseDefault;
		value = value ? {
			...this.defaultValue,
			...value
		} : this.defaultValue;
		let defaultParams = {
			'group' : 'social_item_group',
			'options': [
				{ value: 'facebook', label: __( 'Facebook', 'cryptozfree' ) },
				{ value: 'twitter', label: __( 'Twitter', 'cryptozfree' ) },
				{ value: 'instagram', label: __( 'Instagram', 'cryptozfree' ) },
				{ value: 'youtube', label: __( 'YouTube', 'cryptozfree' ) },
				{ value: 'facebook_group', label: __( 'Facebook Group', 'cryptozfree' ) },
				{ value: 'vimeo', label: __( 'Vimeo', 'cryptozfree' ) },
				{ value: 'pinterest', label: __( 'Pinterest', 'cryptozfree' ) },
				{ value: 'linkedin', label: __( 'Linkedin', 'cryptozfree' ) },
				{ value: 'medium', label: __( 'Medium', 'cryptozfree' ) },
				{ value: 'wordpress', label: __( 'WordPress', 'cryptozfree' ) },
				{ value: 'reddit', label: __( 'Reddit', 'cryptozfree' ) },
				{ value: 'patreon', label: __( 'Patreon', 'cryptozfree' ) },
				{ value: 'github', label: __( 'GitHub', 'cryptozfree' ) },
				{ value: 'dribbble', label: __( 'Dribbble', 'cryptozfree' ) },
				{ value: 'behance', label: __( 'Behance', 'cryptozfree' ) },
				{ value: 'vk', label: __( 'VK', 'cryptozfree' ) },
				{ value: 'xing', label: __( 'Xing', 'cryptozfree' ) },
				{ value: 'rss', label: __( 'RSS', 'cryptozfree' ) },
				{ value: 'email', label: __( 'Email', 'cryptozfree' ) },
				{ value: 'phone', label: __( 'Phone', 'cryptozfree' ) },
				{ value: 'whatsapp', label: __( 'WhatsApp', 'cryptozfree' ) },
				{ value: 'google_reviews', label: __( 'Google Reviews', 'cryptozfree' ) },
				{ value: 'telegram', label: __( 'Telegram', 'cryptozfree' ) },
				{ value: 'yelp', label: __( 'Yelp', 'cryptozfree' ) },
				{ value: 'trip_advisor', label: __( 'Trip Advisor', 'cryptozfree' ) },
				{ value: 'imdb', label: __( 'IMDB', 'cryptozfree' ) },
				{ value: 'soundcloud', label: __( 'SoundCloud', 'cryptozfree' ) },
				{ value: 'tumblr', label: __( 'Tumblr', 'cryptozfree' ) },
				{ value: 'discord', label: __( 'Discord', 'cryptozfree' ) },
				{ value: 'tiktok', label: __( 'TikTok', 'cryptozfree' ) },
				{ value: 'spotify', label: __( 'Spotify', 'cryptozfree' ) },
				{ value: 'apple_podcasts', label: __( 'Apple Podcast', 'cryptozfree' ) },
				{ value: 'flickr', label: __( 'Flickr', 'cryptozfree' ) },
				{ value: '500px', label: __( '500PX', 'cryptozfree' ) },
				{ value: 'bandcamp', label: __( 'Bandcamp', 'cryptozfree' ) },
			],
		};
		this.controlParams = this.props.control.params.input_attrs ? {
			...defaultParams,
			...this.props.control.params.input_attrs,
		} : defaultParams;
		let availibleSocialOptions = [];
		this.controlParams.options.map( ( option ) => {
			if ( ! value.items.some( obj => obj.id === option.value ) ) {
				availibleSocialOptions.push( option );
			}
		} );
		this.state = {
			value: value,
			isVisible: false,
			control: ( undefined !== availibleSocialOptions[0] && undefined !== availibleSocialOptions[0].value ? availibleSocialOptions[0].value : '' ),
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
	saveArrayUpdate( value, index ) {
		let updateState = this.state.value;
		let items = updateState.items;

		const newItems = items.map( ( item, thisIndex ) => {
			if ( index === thisIndex ) {
				item = { ...item, ...value };
			}

			return item;
		} );
		updateState.items = newItems;
		this.setState( { value: updateState } );
		this.updateValues( updateState );
	}
	toggleEnableItem( value, itemIndex ) {
		this.saveArrayUpdate( { enabled: value }, itemIndex );
	}
	onChangeLabel( value, itemIndex ) {
		this.saveArrayUpdate( { label: value }, itemIndex );
	}
	onChangeIcon( value, itemIndex ) {
		this.saveArrayUpdate( { icon: value }, itemIndex );
	}
	onChangeURL( value, itemIndex ) {
		this.saveArrayUpdate( { url: value }, itemIndex );
	}
	onChangeAttachment( value, itemIndex ) {
		this.saveArrayUpdate( { imageid: value }, itemIndex );
	}
	onChangeWidth( value, itemIndex ) {
		this.saveArrayUpdate( { width: value }, itemIndex );
	}
	onChangeSource( value, itemIndex ) {
		this.saveArrayUpdate( { source: value }, itemIndex );
	}
	removeItem( itemIndex ) {
		let updateState = this.state.value;
		let update = updateState.items;
		let updateItems = [];
		{ update.length > 0 && (
			update.map( ( old, index ) => {
				if ( itemIndex !== index ) {
					updateItems.push( old );
				}
			} )
		) };
		updateState.items = updateItems;
		this.setState( { value: updateState } );
		this.updateValues( updateState );
	}
	addItem() {
		const itemControl = this.state.control;
		this.setState( { isVisible: false } );
		if ( itemControl ) {
			let updateState = this.state.value;
			let update = updateState.items;
			const itemLabel = this.controlParams.options.filter(function(o){return o.value === itemControl;} );
			let newItem = {
				'id': itemControl,
				'enabled': true,
				'source': 'icon',
				'url': '',
				'imageid': '',
				'width': 24,
				'icon': itemControl,
				'label': itemLabel[0].label,
			};
			update.push( newItem );
			updateState.items = update;
			let availibleSocialOptions = [];
			this.controlParams.options.map( ( option ) => {
				if ( ! update.some( obj => obj.id === option.value ) ) {
					availibleSocialOptions.push( option );
				}
			} );
			this.setState( { control: ( undefined !== availibleSocialOptions[0] && undefined !== availibleSocialOptions[0].value ? availibleSocialOptions[0].value : '' ) } );
			this.setState( { value: updateState } );
			this.updateValues( updateState );
		}
	}
	onDragEnd( items ) {
		let updateState = this.state.value;
		let update = updateState.items;
		let updateItems = [];
		{ items.length > 0 && (
			items.map( ( item ) => {
				update.filter( obj => {
					if ( obj.id === item.id ) {
						updateItems.push( obj );
					}
				} )
			} )
		) };
		if ( ! this.arraysEqual( update, updateItems ) ) {
			update.items = updateItems;
			updateState.items = updateItems;
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
		const currentList = ( typeof this.state.value != "undefined" && this.state.value.items != null && this.state.value.items.length != null && this.state.value.items.length > 0 ? this.state.value.items : [] );
		let theItems = [];
		{ currentList.length > 0 && (
			currentList.map( ( item ) => {
				theItems.push(
					{
						id: item.id,
					}
				)
			} )
		) };
		const availibleSocialOptions = [];
		this.controlParams.options.map( ( option ) => {
			if ( ! theItems.some( obj => obj.id === option.value ) ) {
				availibleSocialOptions.push( option );
			}
		} )
		const toggleClose = () => {
			if ( this.state.isVisible === true ) {
				this.setState( { isVisible: false } );
			}
		};
		return (
			<div className="cryptozfree-control-field cryptozfree-sorter-items">
				<div className="cryptozfree-sorter-row">
					<ReactSortable animation={100} onStart={ () => this.onDragStop() } onEnd={ () => this.onDragStop() } group={ this.controlParams.group } className={ `cryptozfree-sorter-drop cryptozfree-sorter-sortable-panel cryptozfree-sorter-drop-${ this.controlParams.group }` } handle={ '.cryptozfree-sorter-item-panel-header' } list={ theItems } setList={ ( newState ) => this.onDragEnd( newState ) } >
						{ currentList.length > 0 && (
							currentList.map( ( item, index ) => {
								return <ItemComponent removeItem={ ( remove ) => this.removeItem( remove ) } toggleEnabled={ ( enable, itemIndex ) => this.toggleEnableItem( enable, itemIndex ) } onChangeLabel={ ( label, itemIndex ) => this.onChangeLabel( label, itemIndex ) } onChangeSource={ ( source, itemIndex ) => this.onChangeSource( source, itemIndex ) } onChangeWidth={ ( width, itemIndex ) => this.onChangeWidth( width, itemIndex ) } onChangeURL={ ( url, itemIndex ) => this.onChangeURL( url, itemIndex ) } onChangeAttachment={ ( imageid, itemIndex ) => this.onChangeAttachment( imageid, itemIndex ) } onChangeIcon={ ( icon, itemIndex ) => this.onChangeIcon( icon, itemIndex ) } key={ item.id } index={ index } item={ item } controlParams={ this.controlParams } />;
							} )
						) }
					</ReactSortable>
				</div>
				{ undefined !== availibleSocialOptions[0] && undefined !== availibleSocialOptions[0].value && (
					<div className="cryptozfree-social-add-area">
						{/* <SelectControl
							value={ this.state.control }
							options={ availibleSocialOptions }
							onChange={ value => {
								this.setState( { control: value } );
							} }
						/> */}
						{ this.state.isVisible && (
							<Popover position="top right" className="cryptozfree-popover-color cryptozfree-popover-social" onClose={ toggleClose }>
								<div className="cryptozfree-popover-social-list">
									<ButtonGroup className="cryptozfree-radio-container-control">
										{ availibleSocialOptions.map( ( item, index ) => {
											return (
												<Fragment>
													<Button
														isTertiary
														className={ 'social-radio-btn' }
														onClick={ () => {
															this.setState( { control: availibleSocialOptions[index].value } );
															this.state.control = availibleSocialOptions[index].value;
															this.addItem();
														} }
													>
														{ availibleSocialOptions[index].label && (
															availibleSocialOptions[index].label
														) }
													</Button>
												</Fragment>
											);
										} ) }
									</ButtonGroup>
								</div>
							</Popover>
						) }
						<Button
							className="cryptozfree-sorter-add-item"
							isPrimary
							onClick={ () => {
								this.setState( { isVisible: true } );
							} }
						>
							{ __( 'Add Social', 'cryptozfree' ) }
							<Dashicon icon="plus"/>
						</Button>
						{/* <Button
							className="cryptozfree-sorter-add-item"
							isPrimary
							onClick={ () => {
								this.addItem();
							} }
						>
							{ __( 'Add Item', 'cryptozfree' ) }
							<Dashicon icon="plus"/>
						</Button> */}
					</div>
				) }
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

SocialComponent.propTypes = {
	control: PropTypes.object.isRequired,
};

export default SocialComponent;
