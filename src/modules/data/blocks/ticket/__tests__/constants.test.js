/**
 * Internal dependencies
 */
import { constants } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Ticket Constants', () => {
	const keys = Object.keys( constants );

	keys.forEach( ( key ) => {
		test( key, () => {
			expect( constants[ key ] ).toMatchSnapshot();
		} );
	} );
} );
