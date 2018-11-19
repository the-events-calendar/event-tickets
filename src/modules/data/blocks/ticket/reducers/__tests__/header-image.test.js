/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../header-image';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Header reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the header', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsHeaderImage( { id: 123, src: 'src', alt: 'alt' } ),
		) ).toMatchSnapshot();
	} );
} );
