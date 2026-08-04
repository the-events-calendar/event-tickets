/**
 * Internal dependencies
 */
import * as actions from '../actions';
import {
	getAttendanceCountsFromV2Ticket,
	hydrateRsvpAttendanceCounts,
} from '../utils/hydrate-rsvp-attendance-counts';

describe( 'hydrate-rsvp-attendance-counts', () => {
	describe( 'getAttendanceCountsFromV2Ticket', () => {
		it( 'parses sold, not_going_count, and stock from a full ticket response', () => {
			expect(
				getAttendanceCountsFromV2Ticket( {
					sold: 3,
					not_going_count: 2,
					stock: 7,
				} )
			).toEqual( {
				goingCount: 3,
				notGoingCount: 2,
				inventory: 7,
			} );
		} );

		it( 'prefers going_count over sold when both are present', () => {
			expect(
				getAttendanceCountsFromV2Ticket( {
					going_count: 5,
					sold: 3,
				} )
			).toEqual( {
				goingCount: 5,
			} );
		} );

		it( 'returns an empty object for partial responses without count fields', () => {
			expect( getAttendanceCountsFromV2Ticket( { id: 42 } ) ).toEqual( {} );
		} );
	} );

	describe( 'hydrateRsvpAttendanceCounts', () => {
		it( 'only dispatches actions for provided count fields', () => {
			const dispatch = jest.fn();

			hydrateRsvpAttendanceCounts( dispatch, actions, {
				goingCount: 4,
			} );

			expect( dispatch ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith( actions.setRSVPGoingCount( 4 ) );
		} );
	} );
} );
