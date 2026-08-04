/**
 * Dispatches RSVP details actions synchronously (bypasses saga batching).
 *
 * @param {Function} dispatch Redux dispatch.
 * @param {Object}   actions  RSVP action creators.
 * @param {Object}   details  RSVP details payload.
 */
export const dispatchRsvpDetails = ( dispatch, actions, details ) => {
	const {
		title,
		description,
		capacity,
		notGoingResponses,
		startDate,
		startDateInput,
		startDateMoment,
		startTime,
		endDate,
		endDateInput,
		endDateMoment,
		endTime,
		startTimeInput,
		endTimeInput,
	} = details;

	dispatch( actions.setRSVPTitle( title ) );
	dispatch( actions.setRSVPDescription( description ) );
	dispatch( actions.setRSVPCapacity( capacity ) );
	dispatch( actions.setRSVPNotGoingResponses( notGoingResponses ) );
	dispatch( actions.setRSVPStartDate( startDate ) );
	dispatch( actions.setRSVPStartDateInput( startDateInput ) );
	dispatch( actions.setRSVPStartDateMoment( startDateMoment ) );
	dispatch( actions.setRSVPStartTime( startTime ) );
	dispatch( actions.setRSVPEndDate( endDate ) );
	dispatch( actions.setRSVPEndDateInput( endDateInput ) );
	dispatch( actions.setRSVPEndDateMoment( endDateMoment ) );
	dispatch( actions.setRSVPEndTime( endTime ) );
	dispatch( actions.setRSVPStartTimeInput( startTimeInput ) );
	dispatch( actions.setRSVPEndTimeInput( endTimeInput ) );
};

/**
 * Dispatches RSVP temp details actions synchronously (bypasses saga batching).
 *
 * @param {Function} dispatch    Redux dispatch.
 * @param {Object}   actions     RSVP action creators.
 * @param {Object}   tempDetails RSVP temp details payload.
 */
export const dispatchRsvpTempDetails = ( dispatch, actions, tempDetails ) => {
	const {
		tempTitle,
		tempDescription,
		tempCapacity,
		tempNotGoingResponses,
		tempStartDate,
		tempStartDateInput,
		tempStartDateMoment,
		tempStartTime,
		tempEndDate,
		tempEndDateInput,
		tempEndDateMoment,
		tempEndTime,
		tempStartTimeInput,
		tempEndTimeInput,
	} = tempDetails;

	dispatch( actions.setRSVPTempTitle( tempTitle ) );
	dispatch( actions.setRSVPTempDescription( tempDescription ) );
	dispatch( actions.setRSVPTempCapacity( tempCapacity ) );
	dispatch( actions.setRSVPTempNotGoingResponses( tempNotGoingResponses ) );
	dispatch( actions.setRSVPTempStartDate( tempStartDate ) );
	dispatch( actions.setRSVPTempStartDateInput( tempStartDateInput ) );
	dispatch( actions.setRSVPTempStartDateMoment( tempStartDateMoment ) );
	dispatch( actions.setRSVPTempStartTime( tempStartTime ) );
	dispatch( actions.setRSVPTempEndDate( tempEndDate ) );
	dispatch( actions.setRSVPTempEndDateInput( tempEndDateInput ) );
	dispatch( actions.setRSVPTempEndDateMoment( tempEndDateMoment ) );
	dispatch( actions.setRSVPTempEndTime( tempEndTime ) );
	dispatch( actions.setRSVPTempStartTimeInput( tempStartTimeInput ) );
	dispatch( actions.setRSVPTempEndTimeInput( tempEndTimeInput ) );
};
