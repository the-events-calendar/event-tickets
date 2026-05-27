/**
 * Internal dependencies
 */
import RSVP from '@moderntribe/tickets/blocks/rsvp';

describe( 'RSVP block declaration', () => {
	it( 'uses block apiVersion 3', () => {
		expect( RSVP.apiVersion ).toBe( 3 );
	} );

	it( 'registers expected key settings', () => {
		expect( RSVP.id ).toBe( 'rsvp' );
		expect( RSVP.category ).toBe( 'tribe-tickets' );
		expect( RSVP.supports ).toEqual(
			expect.objectContaining( {
				html: false,
				multiple: false,
			} )
		);
	} );
} );
