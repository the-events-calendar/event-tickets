/**
 * Internal dependencies
 */
import tickets, { byId, allIds } from '../tickets';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'January 10, 2018 5:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Tickets reducer', () => {
	describe( 'byId', () => {
		it( 'should set the default state', () => {
			expect( byId( undefined, {} ) ).toEqual( {} );
		} );

		it( 'should register ticket block', () => {
			expect( byId(
				{},
				actions.registerTicketBlock( 'tribe' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket block', () => {
			expect( byId(
				{ one: {}, two: {} },
				actions.removeTicketBlock( 'one' ),
			) ).toMatchSnapshot();
		} );
	} );

	describe( 'allIds', () => {
		it( 'should set the default state', () => {
			expect( allIds( undefined, {} ) ).toMatchSnapshot();
		} );

		it( 'should register ticket block', () => {
			expect( allIds(
				[],
				actions.registerTicketBlock( 'tribe' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket block', () => {
			expect( allIds(
				[ 'one', 'two' ],
				actions.removeTicketBlock( 'one' ),
			) ).toMatchSnapshot();
		} );
	} );

	describe( 'tickets', () => {
		it( 'should set the default state', () => {
			expect( tickets( undefined, {} ) ).toMatchSnapshot();
		} );
	} );
} );
