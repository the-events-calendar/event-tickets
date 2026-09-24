/**
 * The staging module against a minimal classic editor DOM: where the first-ticket table lands,
 * how a staged row is filled, and what happens to the leave warning on a post submit.
 */
import $ from 'jquery';

const rowTemplate = `
<template id="tec-tickets-deferred-save-row">
	<tr class="tec-tickets-deferred-save-row" data-tec-deferred-save-position="">
		<td><span data-tec-slot="name"></span></td>
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

const load = () => {
	document.body.innerHTML = `
		<form id="post">
			<input id="wp-preview" value="">
			<div id="event_tickets">
				<div id="tribe_panel_base">
					<div class="tribe_sectionheader ticket_list_container"><div class="ticket_table_intro"></div></div>
					<div class="tribe-ticket-control-wrap"></div>
				</div>
				<div id="tribe_panel_edit"><input name="ticket_name" value="Draft"></div>
			</div>
			<div id="tec-tickets-deferred-save"></div>
			${ rowTemplate }
		</form>`;
	window.jQuery = $;
	window.tribe = { tickets: {} };
	jest.isolateModules( () => require( '../../deferred-save' ) );
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

		module.state.dropCreate( 0 );
		module.render();
		expect( document.querySelector( '.ticket_list_wrapper' ) ).toBeNull();
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
} );
