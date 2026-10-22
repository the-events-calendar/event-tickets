import React from 'react';
import renderer from 'react-test-renderer';
import { applyFilters } from '@wordpress/hooks';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import '../../../src/Tickets/Recurring_Tickets/app/block-editor';

// Reads Events Calendar Pro's recurrence store, which is not loaded in tests.
jest.mock( '@moderntribe/common/utils/recurrence', () => ( { hasRecurrenceRules: jest.fn( () => false ) } ) );

// The shared stub has no _x() and no numbered placeholders; with no translations loaded, the real one returns English.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );


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
	test( 'the recurrence block says how to sell tickets for every date instead', () => {
		const message = applyFilters(
			'tec.eventsPro.recurrence.notSupportedWithTicketsMessage',
			"This event has tickets or RSVPs, so it can't be made a recurring event."
		);

		expect( message ).toBe(
			"This event has tickets or RSVPs, so it can't be made a recurring event. To sell tickets for every date, create a new recurring event and add recurring event tickets there."
		);
	} );

	describe( 'the empty state', () => {
		const SeriesPassesNotice = () => <div>Create and manage Series Passes from the Series admin.</div>;
		const empty = {
			Warning: SeriesPassesNotice,
			canCreateTickets: true,
			hasCreatedTickets: false,
			hasRecurrenceRules: true,
			postTypeLabel: 'event',
			showWarning: true,
		};
		const inactive = ( props ) => applyFilters( 'tec.tickets.blocks.Tickets.Inactive.mappedProps', props, { state: {}, ownProps: {} } );

		test( 'says the tickets are for every date, above the Series Passes notice', () => {
			const props = inactive( empty );
			const text = JSON.stringify( renderer.create( <props.Warning /> ).toJSON() );

			expect( props.showWarning ).toBe( true );
			expect( text ).toContain( 'Create tickets for every date in this recurring event.' );
			expect( text ).toContain( 'Create and manage Series Passes from the Series admin.' );
			expect( text.indexOf( 'every date' ) ).toBeLessThan( text.indexOf( 'Series Passes' ) );
		} );

		test( 'says the tickets are for every date when there is no Series Passes notice', () => {
			const props = inactive( { ...empty, showWarning: false } );
			const text = JSON.stringify( renderer.create( <props.Warning /> ).toJSON() );

			expect( text ).toContain( 'Create tickets for every date in this recurring event.' );
			expect( text ).not.toContain( 'Series Passes' );
		} );

		test( 'keeps the same component between renders, so it is not remounted', () => {
			expect( inactive( empty ).Warning ).toBe( inactive( { ...empty } ).Warning );
		} );

		test.each( [
			[ 'a single event', { hasRecurrenceRules: false } ],
			[ 'a recurring event with tickets', { hasCreatedTickets: true } ],
			[ 'a site without ecommerce', { canCreateTickets: false } ],
		] )( 'leaves %s as it is', ( _label, overrides ) => {
			const props = { ...empty, ...overrides };

			expect( inactive( props ) ).toBe( props );
		} );
	} );

	describe( 'the ticket type in the ticket form', () => {
		const standard = { typeName: 'Standard Ticket', typeDescription: 'A standard ticket is specific to this event.' };
		const typeProps = ( { recurs, type } ) => {
			hasRecurrenceRules.mockReturnValue( recurs );

			return applyFilters( 'tec.tickets.blocks.Tickets.Type.mappedProps', { ...standard }, {
				state: {},
				ownProps: {},
				ticketDetails: { type },
			} );
		};

		afterEach( () => {
			hasRecurrenceRules.mockReturnValue( false );
		} );

		test.each( [
			[ 'a new ticket on a recurring event', { recurs: true, type: 'default' } ],
			[ 'a recurring event ticket', { recurs: false, type: 'recurring' } ],
		] )( 'names %s a recurring event ticket', ( _label, ticket ) => {
			expect( typeProps( ticket ) ).toEqual( {
				typeName: 'Recurring Event Ticket',
				typeDescription: 'Sold for each date of this recurring event.',
			} );
		} );

		test( 'leaves a standard ticket on a single event as it is', () => {
			expect( typeProps( { recurs: false, type: 'default' } ) ).toEqual( standard );
		} );
	} );
} );
