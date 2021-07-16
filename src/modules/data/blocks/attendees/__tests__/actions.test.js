/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/attendees';

describe( '[STORE] - Attendees actions', () => {
	it( 'Should set initial state', () => {
		expect( actions.setInitialState( {} ) ).toMatchSnapshot();
	} );

	it( 'Should set the attendees Title', () => {
		expect( actions.setTitle( 'Who\'s coming?' ) ).toMatchSnapshot();
	} );

	it( 'Should set the attendees Display Title', () => {
		expect( actions.setDisplayTitle( true ) ).toMatchSnapshot();
	} );

	it( 'Should set the attendees Display Subtitle', () => {
		expect( actions.setDisplaySubtitle( true ) ).toMatchSnapshot();
	} );
} );
