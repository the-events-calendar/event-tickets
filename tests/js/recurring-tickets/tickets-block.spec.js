import React from 'react';
import renderer from 'react-test-renderer';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import { selectors } from '../../../src/modules/data/blocks/ticket';
import { RecurringTicketsActions } from '../../../src/Tickets/Recurring_Tickets/app/block-editor/tickets-block';
import Card from '../../../src/modules/elements/card/element';

jest.mock( '@moderntribe/common/utils/recurrence', () => ( { hasRecurrenceRules: jest.fn( () => true ) } ) );

const mockInsertBlock = jest.fn();

// The event's tickets over its 5 dates of a pool of 100: Ticket A sold 4, Ticket B 3.
const sales = {
	tickets: { 11: { sold: 4, available: 493 }, 12: { sold: 3, available: 493 } },
	sold: 7,
	available: 493,
};
let mockPost = { tec_tickets_by_date: { sales } };

jest.mock(
	'@wordpress/data',
	() => ( {
		select: ( store ) =>
			'core/editor' === store ? { getCurrentPost: () => mockPost } : { getBlockCount: () => 2 },
		dispatch: jest.fn( () => ( { insertBlock: mockInsertBlock } ) ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/blocks', () => ( { createBlock: ( name ) => ( { name } ) } ), { virtual: true } );

jest.mock( '@moderntribe/common/elements', () => ( {
	Button: ( { children, onClick } ) => <button onClick={ onClick }>{ children }</button>,
} ) );

jest.mock( '../../../src/Tickets/Recurring_Tickets/app/block-editor/tickets-by-date', () => ( {
	TicketsByDateAction: () => <button>Tickets by Date</button>,
} ) );

jest.mock( '../../../src/modules/data/blocks/ticket/constants', () => ( {
	TICKET_LABELS: { ticket: { singular: 'Ticket' } },
} ) );

jest.mock( '../../../src/modules/data/blocks/ticket', () => ( {
	selectors: {
		getTicketId: jest.fn( () => 11 ),
		getTicketCapacityInt: jest.fn( () => 50 ),
		getTicketsSharedCapacityInt: jest.fn( () => 100 ),
		isSharedTicket: jest.fn( () => false ),
	},
} ) );

const context = { state: {}, ownProps: { clientId: 'tickets-block' } };

describe( 'The Tickets block of a recurring event', () => {
	beforeEach( () => {
		hasRecurrenceRules.mockReturnValue( true );
		selectors.isSharedTicket.mockReturnValue( false );
		selectors.getTicketCapacityInt.mockReturnValue( 50 );
		selectors.getTicketId.mockReturnValue( 11 );
		mockPost = { tec_tickets_by_date: { sales } };
	} );

	test( 'is titled for every date, with its buttons in the header', () => {
		const props = applyFilters( 'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps', { showWarning: false }, context );

		expect( props.header ).toBe( 'Recurring Event Tickets' );
		expect( props.headerDescription ).toBe( 'These tickets apply to all dates in the recurring event' );
		expect( props.headerActions.props.clientId ).toBe( 'tickets-block' );
	} );

	test( 'shows what a ticket has available over every date, in parentheses when shared', () => {
		const own = applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 100 }, context );

		selectors.isSharedTicket.mockReturnValue( true );
		const shared = applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 95 }, context );

		expect( own.available ).toBe( 493 );
		expect( shared.available ).toBe( '(493)' );
		expect( shared.amountLabel ).toBeUndefined();
	} );

	test( 'shows the capacity of a ticket not saved with the event yet', () => {
		selectors.getTicketId.mockReturnValue( 99 );

		const props = applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 0 }, context );

		expect( [ props.available, props.amountLabel ] ).toEqual( [ 50, 'capacity' ] );
		expect( applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', { isHidden: false }, context ).isHidden ).toBe( true );
	} );

	test( 'counts a ticket\'s sales over every date, against the shared total when shared', () => {
		const own = applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', { isShared: false, isHidden: false }, context );
		const shared = applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', { isShared: true, isHidden: false }, context );

		expect( [ own.isHidden, own.sold, own.capacity, own.sharedSold ] ).toEqual( [ false, 4, 497, 0 ] );
		expect( [ shared.sold, shared.capacity, shared.sharedSold, shared.sharedCapacity ] ).toEqual( [ 4, 500, 7, 500 ] );
	} );

	test( 'counts the tickets available and the capacity over every date', () => {
		expect( applyFilters( 'tec.tickets.blocks.Tickets.Availability.mappedProps', { available: 95, total: 100 }, context ) ).toEqual( {
			available: 493,
			total: 500,
		} );
	} );

	test( 'leaves a single event\'s block as it is', () => {
		hasRecurrenceRules.mockReturnValue( false );

		expect( applyFilters( 'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps', { a: 1 }, context ) ).toEqual( { a: 1 } );
		expect( applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 9 }, context ) ).toEqual( { available: 9 } );
		expect( applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', { isHidden: false }, context ) ).toEqual( { isHidden: false } );
		expect( applyFilters( 'tec.tickets.blocks.Tickets.Availability.mappedProps', { available: 9 }, context ) ).toEqual( { available: 9 } );
	} );

	test( 'adds a ticket to the end of the block from the header', () => {
		const rendered = renderer.create( <RecurringTicketsActions clientId="tickets-block" /> );
		const add = rendered.root.findAllByType( 'button' ).find( ( button ) => 'Add a Ticket' === button.children.join( '' ) );
		const event = { preventDefault: jest.fn() };

		add.props.onClick( event );

		expect( event.preventDefault ).toHaveBeenCalled();
		expect( dispatch ).toHaveBeenCalledWith( 'core/block-editor' );
		expect( mockInsertBlock ).toHaveBeenCalledWith( { name: 'tribe/tickets-item' }, 2, 'tickets-block' );
	} );

	test( 'puts the buttons beside the card\'s title and description', () => {
		const card = renderer.create( <Card header="Title" description="Help" actions={ <button>Act</button> } /> ).root;

		expect( card.findByProps( { className: 'tickets-heading tickets-heading__title' } ).children ).toEqual( [ 'Title' ] );
		expect( card.findByProps( { className: 'tickets-heading__description' } ).children ).toEqual( [ 'Help' ] );
		expect( card.findByProps( { className: 'tickets-heading__actions' } ).findByType( 'button' ).children ).toEqual( [ 'Act' ] );
	} );
} );
