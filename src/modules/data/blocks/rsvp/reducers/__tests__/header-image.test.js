/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';
import reducer, {
	DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/rsvp/reducers/header-image';

describe( 'Header image reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the header image state', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPHeaderImage( {
			id: 42,
			src: 'test-src',
			alt: 'test-alt',
		} ) ) ).toMatchSnapshot();
	} );
} );
