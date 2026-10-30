jest.mock( '@wordpress/data', () => {
	const insertBlock = jest.fn();

	return {
		select: () => ( { getSelectedBlock: () => null, getBlockCount: () => 2 } ),
		dispatch: () => ( { insertBlock } ),
	};
} );

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: jest.fn( ( name ) => ( { name } ) ),
} ) );

jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => Component,
} ) );

jest.mock( '@moderntribe/common/utils/recurrence', () => ( {
	hasRecurrenceRules: () => false,
} ) );

jest.mock( '../../src/Tickets/Blocks/Tickets/app/editor/action-dashboard/template', () => () => null );

jest.mock( '../../src/modules/data/blocks/ticket', () => ( {
	selectors: {
		getTicketsProvider: () => '',
		hasCreatedTickets: () => false,
	},
	constants: {
		TICKET_ORDERS_PAGE_SLUG: {},
	},
} ) );

import { dispatch } from '@wordpress/data';
import { mapStateToProps } from '../../src/Tickets/Blocks/Tickets/app/editor/action-dashboard/container';

describe( 'Tickets dashboard action', () => {
	it( 'should add a ticket without moving focus to the button', () => {
		const event = { preventDefault: jest.fn() };

		mapStateToProps( {}, { clientId: 'tickets-block' } ).onConfirmClick( event );

		expect( dispatch().insertBlock ).toHaveBeenCalledWith( { name: 'tribe/tickets-item' }, 2, 'tickets-block' );
		expect( event.preventDefault ).toHaveBeenCalled();
	} );
} );
