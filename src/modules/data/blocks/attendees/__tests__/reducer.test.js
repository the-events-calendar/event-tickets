/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/attendees';
import reducer, { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/attendees/reducer';

describe( '[STORE] - Attendees reducer', () => {
	it( 'Should return the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'Should set the title value', () => {
		expect( reducer( DEFAULT_STATE, actions.setTitle( "Who's coming?" ) ) ).toMatchSnapshot();
	} );

	it( 'Should set the display title value', () => {
		expect( reducer( DEFAULT_STATE, actions.setDisplayTitle( true ) ) ).toMatchSnapshot();
	} );

	it( 'Should set the display subtitle value', () => {
		expect( reducer( DEFAULT_STATE, actions.setDisplaySubtitle( true ) ) ).toMatchSnapshot();
	} );
} );
