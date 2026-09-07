/**
 * Internal dependencies
 */
import { selectLatestRsvpTicket } from '../utils/select-latest-rsvp-ticket';

describe( 'selectLatestRsvpTicket', () => {
	it( 'returns the ticket with the highest id', () => {
		expect(
			selectLatestRsvpTicket(
				[
					{ id: 89, type: 'tc-rsvp' },
					{ id: 102, type: 'tc-rsvp' },
					{ id: 50, type: 'default' },
				],
				'tc-rsvp'
			)
		).toEqual( { id: 102, type: 'tc-rsvp' } );
	} );

	it( 'returns null when no matching tickets exist', () => {
		expect( selectLatestRsvpTicket( [ { id: 1, type: 'default' } ], 'tc-rsvp' ) ).toBeNull();
	} );
} );
