/**
 * Internal dependencies
 */
import { types } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Tickets block types', () => {
	const keys = Object.keys( types );

	keys.forEach( ( key ) => {
		test( key, () => {
			expect( types[ key ] ).toMatchSnapshot();
		} );
	} );
} );
