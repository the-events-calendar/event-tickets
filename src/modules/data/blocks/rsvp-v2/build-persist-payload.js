/**
 * Internal dependencies
 */
import * as selectors from '../rsvp-shared/selectors';

/**
 * Builds the RSVP REST payload from the current Redux state.
 *
 * @param {Object} state     The Redux state.
 * @param {Object} overrides Optional field overrides.
 * @return {Object} The payload for create/update thunks.
 */
export const buildPersistPayload = ( state, overrides = {} ) => ( {
	capacity: selectors.getRSVPTempCapacity( state ),
	notGoingResponses: selectors.getRSVPNotGoingResponses( state ),
	startDate: selectors.getRSVPTempStartDate( state ),
	startDateInput: selectors.getRSVPTempStartDateInput( state ),
	startDateMoment: selectors.getRSVPTempStartDateMoment( state ),
	endDate: selectors.getRSVPTempEndDate( state ),
	endDateInput: selectors.getRSVPTempEndDateInput( state ),
	endDateMoment: selectors.getRSVPTempEndDateMoment( state ),
	startTime: selectors.getRSVPTempStartTime( state ),
	endTime: selectors.getRSVPTempEndTime( state ),
	startTimeInput: selectors.getRSVPTempStartTimeInput( state ),
	endTimeInput: selectors.getRSVPTempEndTimeInput( state ),
	id: selectors.getRSVPId( state ),
	...overrides,
} );
