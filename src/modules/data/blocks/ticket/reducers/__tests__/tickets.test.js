/**
 * Internal dependencies
 */
import tickets, { byClientId, allClientIds } from '../tickets';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

jest.mock( 'moment', () => () => {
	const moment = jest.requireActual( 'moment' );
	return moment( 'January 10, 2018 5:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Tickets reducer', () => {
	describe( 'byClientId', () => {
		it( 'should set the default state', () => {
			expect( byClientId( undefined, {} ) ).toEqual( {} );
		} );

		it( 'should register ticket block', () => {
			expect( byClientId(
				{},
				actions.registerTicketBlock( 'tribe' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket block', () => {
			expect( byClientId(
				{ one: {}, two: {} },
				actions.removeTicketBlock( 'one' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket blocks', () => {
			expect( byClientId(
				{ one: {}, two: {} },
				actions.removeTicketBlocks(),
			) ).toMatchSnapshot();
		} );
	} );

	describe( 'allClientIds', () => {
		it( 'should set the default state', () => {
			expect( allClientIds( undefined, {} ) ).toMatchSnapshot();
		} );

		it( 'should register ticket block', () => {
			expect( allClientIds(
				[],
				actions.registerTicketBlock( 'tribe' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket block', () => {
			expect( allClientIds(
				[ 'one', 'two' ],
				actions.removeTicketBlock( 'one' ),
			) ).toMatchSnapshot();
		} );

		it( 'should remove ticket blocks', () => {
			expect( allClientIds(
				[ 'one', 'two' ],
				actions.removeTicketBlocks(),
			) ).toMatchSnapshot();
		} );
	} );

	describe( 'tickets', () => {
		it( 'should set the default state', () => {
			expect( tickets( undefined, {} ) ).toMatchSnapshot();
		} );
	} );
} );
