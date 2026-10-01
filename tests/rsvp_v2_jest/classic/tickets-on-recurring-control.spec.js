/**
 * The Classic Editor control that keeps tickets and recurrence mutually exclusive
 * must treat the RSVP V2 metabox as a ticket form too.
 */

const flushMutations = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

const setup = ( { isRecurring = false, rsvpEnabled = false } = {} ) => {
	document.body.innerHTML = `
		<div class="recurrence-container">
			${ isRecurring ? '<div class="tribe-event-recurrence-rule"></div>' : '' }
		</div>
		<table><tbody>
			<tr class="recurrence-row tribe-datetime-block"></tr>
			<tr class="recurrence-row tec-events-pro-recurrence-not-supported" style="display: none"></tr>
		</tbody></table>
		<div id="tribetickets">
			<div class="tribe-tickets-editor-table-tickets-body"></div>
			<button id="ticket_form_toggle"></button>
		</div>
		<div id="tec-tickets-commerce-rsvp">
			<div class="tec_ticket-panel__recurring-unsupported-warning tec-tickets-rsvp__recurring-warning" style="display: none"></div>
			<div id="tec_tickets_rsvp_metabox">
				<input type="checkbox" id="tec_tickets_rsvp_enable" ${ rsvpEnabled ? 'checked' : '' } />
			</div>
		</div>
	`;

	window.TECFtEditorData = { event: { isRecurring: isRecurring, hasOwnTickets: false } };

	jest.isolateModules( () => {
		require( '../../../src/Tickets/Flexible_Tickets/app/classic-editor/tickets-on-recurring-control' );
	} );
};

const rsvpMetabox = () => document.getElementById( 'tec_tickets_rsvp_metabox' );
const rsvpWarning = () => document.querySelector( '.tec-tickets-rsvp__recurring-warning' );
const addRecurrenceRow = () => document.querySelector( '.recurrence-row.tribe-datetime-block' );

describe( 'Classic Editor tickets-on-recurring control and RSVP V2', () => {
	it( 'hides the RSVP form and shows the warning on a recurring event', () => {
		setup( { isRecurring: true } );

		expect( rsvpMetabox().style.display ).toBe( 'none' );
		expect( rsvpWarning().style.display ).toBe( '' );
	} );

	it( 'shows the RSVP form on a non-recurring event', () => {
		setup();

		expect( rsvpMetabox().style.display ).toBe( '' );
		expect( rsvpWarning().style.display ).toBe( 'none' );
	} );

	it( 'hides the RSVP form when a recurrence rule is added and restores it when removed', async () => {
		setup();
		const container = document.querySelector( '.recurrence-container' );
		const rule = document.createElement( 'div' );
		rule.className = 'tribe-event-recurrence-rule';

		container.appendChild( rule );
		await flushMutations();
		expect( rsvpMetabox().style.display ).toBe( 'none' );

		container.removeChild( rule );
		await flushMutations();
		expect( rsvpMetabox().style.display ).toBe( '' );
	} );

	it( 'hides the recurrence controls when the event has an RSVP', () => {
		setup( { rsvpEnabled: true } );

		expect( addRecurrenceRow().style.display ).toBe( 'none' );
	} );

	it( 'toggles the recurrence controls with the Enable RSVP switch', () => {
		setup();
		const $enable = jQuery( '#tec_tickets_rsvp_enable' );

		$enable.prop( 'checked', true ).trigger( 'change' );
		expect( addRecurrenceRow().style.display ).toBe( 'none' );

		$enable.prop( 'checked', false ).trigger( 'change' );
		expect( addRecurrenceRow().style.display ).toBe( '' );
	} );

	it( 'keeps the recurrence controls hidden when the RSVP is disabled while a ticket is being edited', async () => {
		setup( { rsvpEnabled: true } );
		const editPanel = document.createElement( 'div' );
		editPanel.id = 'tribe_panel_edit';
		editPanel.setAttribute( 'aria-hidden', 'false' );

		document.getElementById( 'tribetickets' ).appendChild( editPanel );
		await flushMutations();
		jQuery( '#tec_tickets_rsvp_enable' ).prop( 'checked', false ).trigger( 'change' );

		expect( addRecurrenceRow().style.display ).toBe( 'none' );
	} );
} );
