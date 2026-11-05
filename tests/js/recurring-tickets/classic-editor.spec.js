import $ from 'jquery';
import { init } from '../../../src/Tickets/Recurring_Tickets/app/classic-editor';

describe( 'Recurring event tickets in the classic editor', () => {
	const recurrenceShown = [];

	beforeEach( () => {
		$( document ).off();
		document.body.className = '';
		document.body.innerHTML = `
			<button id="recurring_ticket_form_toggle" class="ticket_form_toggle" style="display: none"></button>
			<table><tbody class="tribe-tickets-editor-table-tickets-body"></tbody></table>`;
		recurrenceShown.length = 0;
		// What tickets.js does when the event has no tickets any more: show the recurrence rules again.
		$( document ).on( 'tribe-tickets-inactive', () => recurrenceShown.push( true ) );
		init( $, document );
	} );

	const button = () => $( '#recurring_ticket_form_toggle' );
	const addRow = ( type ) =>
		$( '.tribe-tickets-editor-table-tickets-body' ).append( `<tr data-ticket-type="${ type }"></tr>` );

	const addRule = () => $( document.body ).append( '<div class="tribe-event-recurrence tribe-event-recurrence-rule"></div>' );

	test( 'the button shows while the event recurs', () => {
		addRule();
		$( document ).trigger( 'tribe-recurrence-active' );
		expect( button().css( 'display' ) ).not.toBe( 'none' );

		// As when the rule is deleted.
		$( '.tribe-event-recurrence-rule' ).remove();
		$( document ).trigger( 'tribe-recurrence-inactive' );
		expect( button().css( 'display' ) ).toBe( 'none' );
	} );

	test( 'the button stays while the event recurs and the rules are out of sight', () => {
		addRule();
		$( document ).trigger( 'tribe-recurrence-active' );

		// As tickets.js does when a saved ticket refreshes the panel: the rules are out of sight for a moment.
		$( document ).trigger( 'tribe-recurrence-inactive' );

		expect( button().css( 'display' ) ).not.toBe( 'none' );
	} );

	test( 'recurring event tickets leave the recurrence rules editable when tickets.js locks them', () => {
		document.body.classList.add( 'tec-no-tickets-on-recurring' );
		addRow( 'recurring' );

		$( document ).trigger( 'tribe-tickets-active' );

		expect( recurrenceShown ).toHaveLength( 1 );
	} );

	test( 'a standard ticket or an RSVP still locks them', () => {
		document.body.classList.add( 'tec-no-tickets-on-recurring' );
		addRow( 'recurring' );
		addRow( 'default' );
		$( document ).trigger( 'tribe-tickets-active' );

		$( '[data-ticket-type="default"]' ).attr( 'data-ticket-type', 'rsvp' );
		$( document ).trigger( 'tribe-tickets-active' );

		expect( recurrenceShown ).toHaveLength( 0 );
	} );

	test( 'with Flexible Tickets, which locks them by ticket type, nothing is undone', () => {
		addRow( 'recurring' );

		$( document ).trigger( 'tribe-tickets-active' );

		expect( recurrenceShown ).toHaveLength( 0 );
	} );
} );
