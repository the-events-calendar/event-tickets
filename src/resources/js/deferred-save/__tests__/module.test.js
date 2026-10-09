/**
 * The staging module against a minimal classic editor DOM: where the first-ticket table lands,
 * how a staged row is filled, and what happens to the leave warning on a post submit.
 */
import $ from 'jquery';

const rowTemplate = `
<template id="tec-tickets-deferred-save-row">
	<tr class="tec-tickets-deferred-save-row" data-tec-deferred-save-position="">
		<td><span data-tec-slot="name"></span><span class="tec-tickets-deferred-save-badge">Not saved yet</span></td>
		<td><span data-tec-slot="price"></span></td>
		<td><span data-tec-slot="capacity"></span></td>
		<td>
			<button type="button" class="tec-tickets-deferred-save-row__edit"><span class="ticket_edit_text" data-tec-slot="name"></span></button>
			<button type="button" class="tec-tickets-deferred-save-row__remove"><span class="ticket_delete_text" data-tec-slot="name"></span></button>
		</td>
	</tr>
</template>
<template id="tec-tickets-deferred-save-table">
	<div class="ticket_list_wrapper"><table><tbody class="tribe-tickets-editor-table-tickets-body"></tbody></table></div>
</template>`;

const markerTemplate = `
<template id="tec-tickets-deferred-save-marker">
	<span class="tec-tickets-deferred-save-badge" data-tec-slot="marker">
		<span data-tec-slot="marker-text-sr"></span><span data-tec-slot="marker-text"></span>
		<span data-tec-slot="marker-target"></span>
		<button type="button" class="button-link tec-tickets-deferred-save-badge__undo">Undo</button>
	</span>
	<span hidden data-tec-marker-text="staged">Not saved yet</span>
	<span hidden data-tec-marker-text="delete">Will be deleted on save</span>
	<span hidden data-tec-marker-text="move">Moves on save</span>
</template>`;

const savedRow = ( ticketId, ticketType = 'default' ) => `
	<table><tbody class="tribe-tickets-editor-table-tickets-body" data-ticket-type="${ ticketType }"><tr data-ticket-type-id="${ ticketId }">
		<td><div class="tribe-tickets__tickets-editor-ticket-name-title">Saved</div></td>
		<td>
			<button type="button" class="ticket_edit_button">Edit</button>
			<button type="button" class="ticket_duplicate">Duplicate</button>
			<button type="button" class="ticket_delete" attr-ticket-id="${ ticketId }">Delete</button>
		</td>
	</tr></tbody></table>`;

let loadedHooks = null;

const load = ( {
	strings = {},
	editPanel = '<input name="ticket_name" value="Draft">',
	savedTables = savedRow( 12, 'rsvp' ),
} = {} ) => {
	document.body.innerHTML = `
		<form id="post">
			<input id="wp-preview" value="">
			<input name="post_title" value="Post">
			<div id="event_tickets">
				<div id="tribe_panel_base">
					<div class="tribe_sectionheader ticket_list_container"><div class="ticket_table_intro"></div>${ savedTables }</div>
					<div class="tribe-ticket-control-wrap"></div>
				</div>
				<div id="tribe_panel_edit">${ editPanel }</div>
			</div>
			<div id="tec-tickets-deferred-save"></div>
			${ rowTemplate }
			${ markerTemplate }
		</form>
		<div class="wp-header-end"></div>
		<input type="submit" id="publish" disabled>`;
	window.jQuery = $;
	window.tribe = { tickets: {} };
	window.tecTicketsDeferredSave = strings;
	jest.isolateModules( () => {
		// The module's own hooks instance, to fire the panel script's actions at it.
		loadedHooks = require( '@wordpress/hooks' );
		require( '../../deferred-save' );
	} );
	// jQuery runs the module's DOM-ready render on a timer; flush it so it does not land mid-test.
	jest.runAllTimers();

	return window.tribe.tickets.deferredSave;
};

const hasLeaveWarning = () => Boolean( ( $._data( window, 'events' ) || {} ).beforeunload );

describe( 'deferred-save module', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		$( window ).off( 'beforeunload' );
		jest.useRealTimers();
	} );

	it( 'puts the first staged ticket into a table inside the list container and fills every name slot', () => {
		const module = load();
		module.state.stageCreate( [
			[ 'ticket_name', 'General' ],
			[ 'ticket_price', '12' ],
			[ 'tribe-ticket[capacity]', '50' ],
		] );
		module.render();

		const wrapper = document.querySelector( '.ticket_list_container > .ticket_list_wrapper' );
		expect( wrapper ).not.toBeNull();
		expect( wrapper.classList.contains( 'tec-tickets-deferred-save-table' ) ).toBe( true );

		const row = wrapper.querySelector( '.tec-tickets-deferred-save-row' );
		expect( [ ...row.querySelectorAll( '[data-tec-slot="name"]' ) ].map( ( el ) => el.textContent ) ).toEqual( [
			'General',
			'General',
			'General',
		] );
		expect( row.querySelector( '[data-tec-slot="price"]' ).textContent ).toBe( '12' );
		expect( row.querySelector( '.tec-tickets-deferred-save-badge' ) ).not.toBeNull();

		module.state.dropCreate( 0 );
		module.render();
		expect( document.querySelector( '.ticket_list_wrapper' ) ).toBeNull();
	} );

	it( 'puts a staged ticket into the table of its own ticket type, not the first table', () => {
		const module = load( { savedTables: savedRow( 12, 'rsvp' ) + savedRow( 13, 'default' ) } );
		module.state.stageCreate( [ [ 'ticket_name', 'General' ], [ 'ticket_type', 'default' ] ] );
		module.render();

		const $row = $( '.tec-tickets-deferred-save-row' );
		expect( $row.closest( 'tbody' ).attr( 'data-ticket-type' ) ).toBe( 'default' );
		expect( $row.closest( 'tbody' ).find( 'tr[data-ticket-type-id="13"]' ).length ).toBe( 1 );
		expect( document.querySelector( '.tec-tickets-deferred-save-table' ) ).toBeNull();
	} );

	it( 'drops the leave warning when the post form submits for real', () => {
		const module = load();
		module.state.stageCreate( [ [ 'ticket_name', 'General' ] ] );
		module.render();
		expect( hasLeaveWarning() ).toBe( true );

		document.getElementById( 'post' ).dispatchEvent( new window.Event( 'submit', { cancelable: true } ) );
		jest.runAllTimers();

		expect( hasLeaveWarning() ).toBe( false );
	} );

	it( 'keeps the leave warning when the submit was prevented or is a preview', () => {
		const module = load();
		module.state.stageCreate( [ [ 'ticket_name', 'General' ] ] );
		module.render();

		$( '#post' ).on( 'submit', ( event ) => event.preventDefault() );
		document.getElementById( 'post' ).dispatchEvent( new window.Event( 'submit', { cancelable: true } ) );
		jest.runAllTimers();
		expect( hasLeaveWarning() ).toBe( true );

		$( '#post' ).off( 'submit' );
		const preview = load();
		preview.state.stageCreate( [ [ 'ticket_name', 'General' ] ] );
		preview.render();
		document.getElementById( 'wp-preview' ).value = 'dopreview';
		document.getElementById( 'post' ).dispatchEvent( new window.Event( 'submit', { cancelable: true } ) );
		jest.runAllTimers();
		expect( hasLeaveWarning() ).toBe( true );
	} );

	it( 'gives a row its buttons back when its staged delete is undone', () => {
		const module = load();
		module.state.stageDelete( 12 );
		module.render();
		expect( $( 'tr[data-ticket-type-id="12"] .ticket_edit_button' ).prop( 'disabled' ) ).toBe( true );

		$( 'tr[data-ticket-type-id="12"] .tec-tickets-deferred-save-badge__undo' ).trigger( 'click' );

		expect( $( 'tr[data-ticket-type-id="12"] .ticket_edit_button' ).prop( 'disabled' ) ).toBe( false );
		expect( $( 'tr[data-ticket-type-id="12"] .ticket_delete' ).prop( 'disabled' ) ).toBe( false );
	} );

	it( 'does not let a ticket with a staged move be edited, and says why', () => {
		const module = load( { strings: { editBlocked: 'Undo the move to edit this ticket.' } } );
		module.stageMove( 12, 99, 'Other event' );

		const $edit = $( 'tr[data-ticket-type-id="12"] .ticket_edit_button' );
		expect( $edit.prop( 'disabled' ) ).toBe( true );
		expect( $edit.attr( 'title' ) ).toBe( 'Undo the move to edit this ticket.' );
	} );

	it( 'offers no move on a ticket with a staged edit, and says why', () => {
		const module = load( {
			strings: { moveBlocked: 'Save the post before moving this ticket.' },
			editPanel: '<input id="ticket_id" name="ticket_id" value="12"><a class="tribe-ticket-move-link" href="#">Move</a>',
		} );
		module.state.stageUpdate( 12, [ [ 'ticket_name', 'Edited' ] ] );

		loadedHooks.doAction( 'tec.tickets.admin.panels.refreshed', { swapTo: 'ticket' } );

		expect( $( '.tribe-ticket-move-link' ).css( 'display' ) ).toBe( 'none' );
		expect( $( '#tribe_panel_edit' ).text() ).toContain( 'Save the post before moving this ticket.' );
	} );

	it( 'counts the submit button the browser adds, which PHP counts too', () => {
		// post_title, three staged fields and the completeness marker: five, and the publish button makes six.
		const module = load( { strings: { maxInputVars: 5, inputLimit: 'Too many fields.' } } );
		module.state.stageCreate( [ [ 'ticket_name', 'A' ], [ 'ticket_price', '1' ], [ 'ticket_type', 'default' ] ] );
		module.render();

		const event = new window.Event( 'submit', { cancelable: true } );
		document.getElementById( 'post' ).dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
	} );

	it( 'refuses a submit that would carry more fields than the server reads', () => {
		const module = load( { strings: { maxInputVars: 3, inputLimit: 'Too many fields.' } } );
		module.state.stageCreate( [ [ 'ticket_name', 'A' ], [ 'ticket_price', '1' ], [ 'ticket_type', 'default' ] ] );
		module.render();

		const event = new window.Event( 'submit', { cancelable: true } );
		document.getElementById( 'post' ).dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
		expect( $( '.tec-tickets-deferred-save-validation' ).text() ).toContain( 'Too many fields.' );
		expect( $( '#publish' ).prop( 'disabled' ) ).toBe( false );
	} );
} );
