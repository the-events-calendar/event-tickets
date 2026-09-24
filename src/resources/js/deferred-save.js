/**
 * Stages ticket changes in the classic editor instead of saving them at once.
 *
 * Loaded only on a post that uses deferred save, where the server printed the
 * `#tec-tickets-deferred-save` container, the nonce and the row templates. It answers the
 * `tec.tickets.admin.ticket.intercepted` filter the panel script asks before it saves or deletes,
 * keeps the staged state, writes it as hidden `tec_tickets[...]` inputs into the container, and
 * re-renders staged rows and markers after every panel refresh.
 *
 * @since TBD
 */

import { addFilter, addAction } from '@wordpress/hooks';
import { createState, buildHiddenFields } from './deferred-save/utils';

const CONTAINER = '#tec-tickets-deferred-save';
const NAMESPACE = 'tec/tickets/deferred-save';

( function ( $, obj ) {
	'use strict';

	const $container = $( CONTAINER );

	if ( ! $container.length ) {
		return;
	}

	const strings = window.tecTicketsDeferredSave || {};
	const state = createState();
	let editing = null; // { position } while a staged new ticket is open in the panel.
	let ticketTypeBeingAdded = '';

	const editor = () => tribe.tickets.editor;
	const $panelBase = () => $( '#tribe_panel_base' );
	const $panelEdit = () => $( '#tribe_panel_edit' );
	const $tickets = () => $( '#event_tickets' );

	obj.isEnabled = () => true;
	obj.state = state;

	/**
	 * Reads the edit panel into a field set, adding what the AJAX save adds.
	 *
	 * @return {Array<Array<string>>} The field set.
	 */
	const readPanel = () => {
		const $panel = $panelEdit();
		const fields = $panel
			.find( 'input,textarea,select' )
			.serializeArray()
			.map( ( { name, value } ) => [ name, value ] );
		const has = ( name ) => fields.some( ( [ fieldName ] ) => fieldName === name );

		if ( ! has( 'ticket_provider' ) ) {
			fields.push( [
				'ticket_provider',
				$panel.find( '#tec_tickets_ticket_provider' ).val() || $panel.data( 'default-provider' ) || '',
			] );
		}

		if ( ! has( 'ticket_type' ) ) {
			fields.push( [ 'ticket_type', $panel.find( '#ticket_type' ).val() || ticketTypeBeingAdded || 'default' ] );
		}

		return fields;
	};

	const ticketIdInPanel = () => parseInt( $panelEdit().find( '#ticket_id' ).val(), 10 ) || 0;

	/**
	 * Writes the staged state into the container as hidden inputs.
	 */
	const renderHiddenFields = () => {
		$container.empty();
		buildHiddenFields( state ).forEach( ( [ name, value ] ) => {
			$( '<input>', { type: 'hidden', name, value } ).appendTo( $container );
		} );
	};

	const templateContent = ( id ) => {
		const template = document.getElementById( id );

		return template ? document.importNode( template.content, true ) : null;
	};

	const fillSlot = ( root, slot, text ) => {
		root.querySelectorAll( `[data-tec-slot="${ slot }"]` ).forEach( ( el ) => {
			el.textContent = text;
		} );
	};

	/**
	 * Finds, or creates from the template, the table body staged rows go into.
	 *
	 * The template is the real list table; it goes where the saved lists are printed, inside the
	 * list container, and is marked so it can be removed once nothing is staged.
	 *
	 * @return {jQuery} The table body.
	 */
	const stagedTbody = () => {
		let $tbody = $panelBase().find( '.tribe-tickets-editor-table-tickets-body' ).first();

		if ( $tbody.length ) {
			return $tbody;
		}

		const table = templateContent( 'tec-tickets-deferred-save-table' );

		if ( ! table ) {
			return $tbody;
		}

		const wrapper = table.querySelector( '.ticket_list_wrapper' ) || table.querySelector( 'table' );
		if ( wrapper ) {
			wrapper.classList.add( 'tec-tickets-deferred-save-table' );
		}

		const $listContainer = $panelBase().find( '.ticket_list_container' ).first();
		( $listContainer.length ? $listContainer : $panelBase() ).append( table );
		$tbody = $panelBase().find( '.tec-tickets-deferred-save-table .tribe-tickets-editor-table-tickets-body' );

		return $tbody;
	};

	const priceLabel = ( price ) => ( '' === price || '0' === price ? strings.free || '' : price );
	const capacityLabel = ( capacity ) => ( '' === capacity ? strings.unlimited || '' : capacity );

	/**
	 * Appends a row for every staged new ticket.
	 */
	const renderStagedRows = () => {
		$panelBase().find( '.tec-tickets-deferred-save-row' ).remove();
		const { create } = state.toPayload();

		if ( ! create.length ) {
			$panelBase().find( '.tec-tickets-deferred-save-table' ).remove();
			return;
		}

		const $tbody = stagedTbody();

		create.forEach( ( { summary }, position ) => {
			const row = templateContent( 'tec-tickets-deferred-save-row' );
			if ( ! row ) {
				return;
			}
			const tr = row.querySelector( 'tr' );
			tr.setAttribute( 'data-tec-deferred-save-position', String( position ) );
			tr.setAttribute( 'data-ticket-type', summary.type || 'default' );
			fillSlot( tr, 'name', summary.name );
			fillSlot( tr, 'price', priceLabel( summary.price ) );
			fillSlot( tr, 'capacity', capacityLabel( summary.capacity ) );
			$tbody.append( tr );
		} );
	};

	const markerText = ( kind ) => {
		const template = document.getElementById( 'tec-tickets-deferred-save-marker' );
		const el = template ? template.content.querySelector( `[data-tec-marker-text="${ kind }"]` ) : null;

		return el ? el.textContent : '';
	};

	/**
	 * Puts a marker on a saved ticket's row.
	 *
	 * @param {jQuery}  $row   The row.
	 * @param {string}  kind   `staged`, `delete` or `move`.
	 * @param {string}  target The destination title for a move.
	 * @param {boolean} undo   Whether to offer Undo.
	 */
	const markRow = ( $row, kind, target = '', undo = false ) => {
		const marker = templateContent( 'tec-tickets-deferred-save-marker' );
		if ( ! marker ) {
			return;
		}
		const badge = marker.querySelector( '[data-tec-slot="marker"]' );
		badge.classList.add( `tec-tickets-deferred-save-badge--${ kind }` );
		const text = markerText( kind );
		fillSlot( badge, 'marker-text', text );
		fillSlot( badge, 'marker-text-sr', `${ text }${ target ? ` ${ target }` : '' }.` );
		fillSlot( badge, 'marker-target', target );
		if ( ! undo ) {
			badge.querySelector( '.tec-tickets-deferred-save-badge__undo' ).remove();
		}
		badge.setAttribute( 'data-tec-marker-kind', kind );
		$row.addClass( `tec-tickets-deferred-save-row--${ kind }` );
		$row.find( '.tribe-tickets__tickets-editor-ticket-name-title' ).first().append( badge );
	};

	/**
	 * Marks saved rows that have a staged edit, deletion or move.
	 */
	const renderMarkers = () => {
		$panelBase().find( '.tec-tickets-deferred-save-badge' ).remove();
		$panelBase()
			.find( '[class*="tec-tickets-deferred-save-row--"]' )
			.removeClass( ( _, classes ) =>
				( classes.match( /tec-tickets-deferred-save-row--\S+/g ) || [] ).join( ' ' )
			);
		const { update, delete: deleted, move } = state.toPayload();

		Object.keys( update ).forEach( ( ticketId ) => {
			markRow( $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` ), 'staged' );
		} );

		deleted.forEach( ( ticketId ) => {
			const $row = $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` );
			markRow( $row, 'delete', '', true );
			$row.find( '.ticket_edit_button, .ticket_duplicate, .ticket_delete' ).prop( 'disabled', true );
		} );

		Object.keys( move ).forEach( ( ticketId ) => {
			const { destinationTitle } = state.getMove( parseInt( ticketId, 10 ) );
			markRow( $panelBase().find( `tr[data-ticket-type-id="${ ticketId }"]` ), 'move', destinationTitle, true );
		} );
	};

	const render = () => {
		renderHiddenFields();
		renderStagedRows();
		renderMarkers();
		bindUnload();
	};

	/**
	 * Warns before leaving while anything is staged.
	 */
	const bindUnload = () => {
		$( window ).off( 'beforeunload.tecDeferredSave' );

		if ( state.hasChanges() ) {
			$( window ).on( 'beforeunload.tecDeferredSave', () => strings.leaveMessage || '' );
		}
	};

	/**
	 * Lays a field set over the open edit panel.
	 *
	 * @param {Array<Array<string>>} fields The field set.
	 */
	const fillPanel = ( fields ) => {
		const $panel = $panelEdit();
		const byName = {};
		fields.forEach( ( [ name, value ] ) => {
			byName[ name ] = byName[ name ] || [];
			byName[ name ].push( value );
		} );

		$panel.find( 'input[type="checkbox"], input[type="radio"]' ).each( function () {
			const values = byName[ this.name ] || [];
			this.checked = values.includes( this.value );
		} );

		Object.entries( byName ).forEach( ( [ name, values ] ) => {
			const $fields = $panel
				.find( '[name]' )
				.filter( ( _, el ) => el.name === name )
				.not( ':checkbox, :radio' );
			if ( $fields.is( 'select[multiple]' ) ) {
				$fields.val( values );
			} else {
				$fields.val( values[ values.length - 1 ] ).trigger( 'change' );
			}
		} );
	};

	/**
	 * Stages the open edit panel: an edit of a saved ticket, a re-edit of a staged one, or a new ticket.
	 */
	const stageSave = () => {
		const fields = readPanel();
		const ticketId = ticketIdInPanel();

		if ( ticketId ) {
			state.stageUpdate( ticketId, fields );
		} else if ( editing ) {
			state.restageCreate( editing.position, fields );
		} else {
			state.stageCreate( fields );
		}

		editing = null;
		$tickets().trigger( 'tec-deferred-save-staged.tribe', [ state.toPayload() ] );
		editor().fetchPanels( null, 'list' );
	};

	const stageDelete = ( ticketId ) => {
		state.stageDelete( ticketId );
		render();
	};

	addFilter( 'tec.tickets.admin.ticket.intercepted', NAMESPACE, ( intercepted, action, context = {} ) => {
		if ( 'save' === action ) {
			ticketTypeBeingAdded = context.ticketType || ticketTypeBeingAdded;
			stageSave();
			return true;
		}

		if ( 'delete' === action ) {
			const ticketId = parseInt( context.ticketId, 10 );
			if ( ticketId ) {
				stageDelete( ticketId );
			}
			return true;
		}

		return intercepted;
	} );

	// After every panel refresh: re-render the staged rows and markers, and lay staged values over an opened form.
	addAction( 'tec.tickets.admin.panels.refreshed', NAMESPACE, ( { swapTo } ) => {
		render();

		if ( 'ticket' !== swapTo ) {
			editing = null;
			return;
		}

		if ( editing ) {
			const staged = state.getCreate( editing.position );
			if ( staged ) {
				fillPanel( staged.fields );
			}
			$panelEdit().find( '.tribe-ticket-move-link' ).hide();
			return;
		}

		const ticketId = ticketIdInPanel();
		const update = ticketId ? state.getUpdate( ticketId ) : null;
		if ( update ) {
			fillPanel( update.fields );
		}
	} );

	// Staged row actions.
	$tickets().on( 'click', '.tec-tickets-deferred-save-row__edit', function () {
		const $row = $( this ).closest( 'tr' );
		const position = parseInt( $row.attr( 'data-tec-deferred-save-position' ), 10 );
		const staged = state.getCreate( position );
		if ( ! staged ) {
			return;
		}
		editing = { position };
		ticketTypeBeingAdded = staged.summary.type || 'default';
		editor().fetchPanels( null, 'ticket', ticketTypeBeingAdded );
	} );

	$tickets().on( 'click', '.tec-tickets-deferred-save-row__remove', function () {
		const position = parseInt( $( this ).closest( 'tr' ).attr( 'data-tec-deferred-save-position' ), 10 );
		state.dropCreate( position );
		render();
	} );

	$tickets().on( 'click', '.tec-tickets-deferred-save-badge__undo', function () {
		const $row = $( this ).closest( 'tr' );
		const ticketId = parseInt( $row.attr( 'data-ticket-type-id' ), 10 );
		const kind = $( this ).closest( '[data-tec-marker-kind]' ).attr( 'data-tec-marker-kind' );
		if ( 'delete' === kind ) {
			state.undoDelete( ticketId );
		} else if ( 'move' === kind ) {
			state.undoMove( ticketId );
		}
		render();
	} );

	// On submit: the staged entries carry everything; an open edit panel must not post its fields as top-level ones.
	// The settings panel is left alone: the post save reads its fields. The inputs are handed back right after the
	// submit. The leave warning comes back only when the page stays: a preview submits to a new tab, and a later
	// handler may cancel the submit.
	$( '#post' ).on( 'submit.tecDeferredSave', ( event ) => {
		const isPreview = 'dopreview' === $( '#wp-preview' ).val();
		$( window ).off( 'beforeunload.tecDeferredSave' );

		if ( isPreview ) {
			setTimeout( bindUnload, 0 );
			return;
		}

		const $inputs = $panelEdit().find( 'input,textarea,select' ).not( ':disabled' );
		$inputs.prop( 'disabled', true );
		setTimeout( () => {
			$inputs.prop( 'disabled', false );
			if ( event.isDefaultPrevented() ) {
				bindUnload();
			}
		}, 0 );
	} );

	obj.stageMove = ( ticketId, destinationId, destinationTitle ) => {
		state.stageMove( parseInt( ticketId, 10 ), parseInt( destinationId, 10 ), destinationTitle || '' );
		render();
	};

	obj.render = render;

	$( render );
} )( jQuery, ( tribe.tickets.deferredSave = tribe.tickets.deferredSave || {} ) );

export default tribe.tickets.deferredSave;
