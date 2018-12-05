/**
 * Internal dependencies
 */
import { selectors } from '@moderntribe/tickets/data/blocks/attendees';
import { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/attendees/reducer';

const state = {
	tickets: {
		blocks: {
			attendees: DEFAULT_STATE,
		},
	},
};

describe( '[STORE] - Attendees selectors', () => {
	it( 'Should return the attendees block', () => {
		expect( selectors.getAttendeesBlock( state ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'Should return the attendees block title', () => {
		expect( selectors.getTitle( state ) ).toEqual( DEFAULT_STATE.title );
	} );
} );
