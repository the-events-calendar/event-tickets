import { applyFilters } from '@wordpress/hooks';
import '../../../src/Tickets/Recurring_Tickets/app/block-editor';

describe( 'Recurring event tickets in the block editor', () => {
	test( 'the Tickets block shows its dashboard on a recurring event', () => {
		const props = applyFilters(
			'tec.tickets.blocks.Tickets.mappedProps',
			{ hasRecurrenceRules: true, noTicketsOnRecurring: true, canCreateTickets: true },
			{ state: {}, ownProps: {} }
		);

		expect( props.noTicketsOnRecurring ).toBe( false );
		expect( props.hasRecurrenceRules ).toBe( true );
	} );

	test( 'the dashboard offers "Add a Ticket" on a recurring event, without the not-supported message', () => {
		// As core and Flexible Tickets leave them on a recurring event.
		const blocked = { showConfirm: false, showNotSupportedMessage: true, showWarning: true, disableSettings: true };

		const props = applyFilters( 'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps', blocked, {
			state: {},
			ownProps: {},
			isRecurring: true,
		} );

		expect( props ).toEqual( { showConfirm: true, showNotSupportedMessage: false, showWarning: false, disableSettings: true } );
	} );

	test( 'a single event keeps its dashboard as it is', () => {
		const single = { showConfirm: true, showNotSupportedMessage: false, showWarning: false, disableSettings: false };

		const props = applyFilters( 'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps', { ...single }, {
			state: {},
			ownProps: {},
			isRecurring: false,
		} );

		expect( props ).toEqual( single );
	} );
} );
