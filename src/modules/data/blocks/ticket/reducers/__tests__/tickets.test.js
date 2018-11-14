/**
 * Internal dependencies
 */
import tickets from '../tickets';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'January 10, 2018 5:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Tickets reducer', () => {
	let state = {};

	beforeEach( () => {
		state = { allIds: [], byId: {} };
	} );

	test( 'Default reducer', () => {
		expect( tickets( undefined, {} ) ).toEqual( state );
	} );

	test( 'Add a new block inside of the reducer', () => {
		expect( tickets( state, actions.registerTicketBlock( 'modern-tribe' ) ) ).toMatchSnapshot();
	} );

	test( 'Remove an existing block from the reducer', () => {
		state = tickets( state, actions.registerTicketBlock( 'modern-tribe' ) );
		expect( tickets( state, actions.removeTicketBlock( 'modern-tribe' ) ) ).toMatchSnapshot();
	} );
} );
