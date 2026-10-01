/**
 * The legacy tickets editor script also runs on the Community Events submission form,
 * where it is the only thing keeping tickets off recurring events. It must treat the
 * RSVP V2 metabox as a ticket form too.
 */

const setup = ( { rsvpEnabled = false } = {} ) => {
	document.body.className = 'tec-no-tickets-on-recurring';
	document.body.innerHTML = `
		<div class="tribe-event-recurrence-rule"></div>
		<table><tbody>
			<tr class="recurrence-row tribe-datetime-block"></tr>
			<tr class="recurrence-row tribe-recurrence-not-supported" style="display: none"></tr>
		</tbody></table>
		<div id="tribetickets">
			<div id="event_tickets"></div>
			<div class="tribe-tickets-editor-table-tickets-body" style="display: none"></div>
			<button id="ticket_form_toggle"></button>
		</div>
		<div id="tec-tickets-commerce-rsvp">
			<div class="tec_ticket-panel__recurring-unsupported-warning tec-tickets-rsvp__recurring-warning" style="display: none"></div>
			<div id="tec_tickets_rsvp_metabox">
				<input type="checkbox" id="tec_tickets_rsvp_enable" ${ rsvpEnabled ? 'checked' : '' } />
			</div>
		</div>
	`;

	global.tribe = {};
	global.TribeTickets = { ajaxurl: '' };
	global.ajaxurl = '';
	global._ = require( 'lodash' );

	jest.isolateModules( () => {
		require( '../../../src/resources/js/tickets' );
	} );
};

const $ = require( 'jquery' );
const rsvpMetabox = () => $( '#tec_tickets_rsvp_metabox' );
const rsvpWarning = () => $( '.tec-tickets-rsvp__recurring-warning' );
const addRecurrenceRow = () => $( '.recurrence-row.tribe-datetime-block' );

describe( 'tickets.js recurrence handling and RSVP V2', () => {
	it( 'hides the RSVP form when recurrence is active and restores it when inactive', () => {
		setup();

		$( document ).trigger( 'tribe-recurrence-active' );
		expect( rsvpMetabox().css( 'display' ) ).toBe( 'none' );
		expect( rsvpWarning().css( 'display' ) ).not.toBe( 'none' );

		$( document ).trigger( 'tribe-recurrence-inactive' );
		expect( rsvpMetabox().css( 'display' ) ).not.toBe( 'none' );
		expect( rsvpWarning().css( 'display' ) ).toBe( 'none' );
	} );

	it( 'hides the recurrence controls when the RSVP is enabled', () => {
		setup( { rsvpEnabled: true } );

		$( document ).trigger( 'verify.dependency' );

		expect( addRecurrenceRow().css( 'display' ) ).toBe( 'none' );
	} );
} );
