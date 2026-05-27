/**
 * Internal dependencies
 */
import Attendees from '../index';

describe( 'Attendees block declaration', () => {
	it( 'uses block apiVersion 3', () => {
		expect( Attendees.apiVersion ).toBe( 3 );
	} );

	it( 'registers expected key settings', () => {
		expect( Attendees.id ).toBe( 'attendees' );
		expect( Attendees.category ).toBe( 'tribe-tickets' );
		expect( Attendees.supports ).toEqual(
			expect.objectContaining( {
				html: false,
				customClassName: false,
			} )
		);
	} );
} );
