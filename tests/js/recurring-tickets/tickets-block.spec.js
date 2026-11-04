import React from 'react';
import renderer from 'react-test-renderer';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import { selectors } from '../../../src/modules/data/blocks/ticket';
import { RecurringTicketsActions } from '../../../src/Tickets/Recurring_Tickets/app/block-editor/tickets-block';
import Card from '../../../src/modules/elements/card/element';

jest.mock( '@moderntribe/common/utils/recurrence', () => ( { hasRecurrenceRules: jest.fn( () => true ) } ) );

const insertBlock = jest.fn();

jest.mock(
	'@wordpress/data',
	() => ( {
		select: () => ( { getBlockCount: () => 2 } ),
		dispatch: jest.fn( () => ( { insertBlock } ) ),
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

jest.mock( '../../../src/modules/data/blocks/ticket', () => ( {
	selectors: {
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
	} );

	test( 'is titled for every date, with its buttons in the header', () => {
		const props = applyFilters( 'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps', { showWarning: false }, context );

		expect( props.header ).toBe( 'Recurring Event Tickets' );
		expect( props.headerDescription ).toBe( 'These tickets apply to all dates in the recurring event' );
		expect( props.headerActions.props.clientId ).toBe( 'tickets-block' );
	} );

	test( 'shows a ticket\'s capacity, in parentheses when shared, in place of what is available', () => {
		const own = applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 100 }, context );

		selectors.isSharedTicket.mockReturnValue( true );
		selectors.getTicketCapacityInt.mockReturnValue( 0 );
		const shared = applyFilters( 'tec.tickets.blocks.Ticket.Price.mappedProps', { available: 95 }, context );

		expect( [ own.available, own.amountLabel ] ).toEqual( [ 50, 'capacity' ] );
		expect( [ shared.available, shared.amountLabel ] ).toEqual( [ '(100)', 'capacity' ] );
	} );

	test( 'leaves out what a date sold and has available', () => {
		expect( applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', { isHidden: false }, context ).isHidden ).toBe( true );
		expect( applyFilters( 'tec.tickets.blocks.Tickets.Availability.mappedProps', { available: 95, total: 100 }, context ) ).toEqual( {
			available: null,
			total: 100,
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
		expect( insertBlock ).toHaveBeenCalledWith( { name: 'tribe/tickets-item' }, 2, 'tickets-block' );
	} );

	test( 'puts the buttons beside the card\'s title and description', () => {
		const card = renderer.create( <Card header="Title" description="Help" actions={ <button>Act</button> } /> ).root;

		expect( card.findByProps( { className: 'tickets-heading tickets-heading__title' } ).children ).toEqual( [ 'Title' ] );
		expect( card.findByProps( { className: 'tickets-heading__description' } ).children ).toEqual( [ 'Help' ] );
		expect( card.findByProps( { className: 'tickets-heading__actions' } ).findByType( 'button' ).children ).toEqual( [ 'Act' ] );
	} );
} );
