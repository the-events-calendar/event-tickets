/**
 * Internal dependencies
 */
import tmp, { DEFAULT_STATE } from '../tmp';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Temporarily reducer', () => {
	test( 'default state', () => {
		expect( tmp( undefined, {} ) ).toBe( DEFAULT_STATE );
	} );

	test( 'shared capacity', () => {
		expect( tmp( DEFAULT_STATE, actions.setTempSharedCapacity( 100 ) ) ).toMatchSnapshot();
	} );
} );
