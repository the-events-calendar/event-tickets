/**
 * Internal dependencies
 */
import { options } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Ticket Options', () => {
	const keys = Object.keys( options );

	keys.forEach( ( key ) => {
		test( key, () => {
			expect( options[ key ] ).toMatchSnapshot();
		} );
	} );
} );
