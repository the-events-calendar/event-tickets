/**
 * External dependencies
 */
import { createSelector } from 'reselect';

/**
 * ------------------------------------------------------------
 * RSVP State
 * ------------------------------------------------------------
 */

export const getRSVPBlock = ( state ) => state.tickets.blocks.rsvp;

export const getRSVPId = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.id,
);

export const getRSVPCreated = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.created,
);

export const getRSVPSettingsOpen = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.settingsOpen,
);

export const getRSVPHasChanges = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.hasChanges,
);

export const getRSVPIsLoading = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.isLoading,
);

export const getRSVPIsSettingsLoading = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.isSettingsLoading,
);

export const getRSVPIsModalOpen = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.isModalOpen,
);

export const getRSVPGoingCount = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.goingCount,
);

export const getRSVPNotGoingCount = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.notGoingCount,
);

export const getRSVPHasAttendeeInfoFields = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.hasAttendeeInfoFields,
);

export const getRSVPHasDurationError = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.hasDurationError,
);

/**
 * ------------------------------------------------------------
 * RSVP Details
 * ------------------------------------------------------------
 */
export const getRSVPDetails = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.details,
);

export const getRSVPTitle = createSelector(
	[ getRSVPDetails ],
	( details ) => details.title,
);

export const getRSVPDescription = createSelector(
	[ getRSVPDetails ],
	( details ) => details.description,
);

export const getRSVPCapacity = createSelector(
	[ getRSVPDetails ],
	( details ) => details.capacity,
);

export const getRSVPAvailable = createSelector(
	[ getRSVPCapacity, getRSVPGoingCount ],
	( capacity, goingCount ) => {
		if ( capacity === '' ) {
			return -1;
		}

		const total = parseInt( capacity, 10 ) || 0;
		const going = parseInt( goingCount, 10 ) || 0;
		/**
		 * Prevent to have negative values when subtracting the going amount from total amount, so it takes the max value
		 * of the substraction operation or zero if the operation is lower than zero it will return zero insted.
		 */
		return Math.max( total - going, 0 );
	}
);

export const getRSVPNotGoingResponses = createSelector(
	[ getRSVPDetails ],
	( details ) => details.notGoingResponses,
);

export const getRSVPStartDate = createSelector(
	[ getRSVPDetails ],
	( details ) => details.startDate,
);

export const getRSVPStartDateInput = createSelector(
	[ getRSVPDetails ],
	( details ) => details.startDateInput,
);

export const getRSVPStartDateMoment = createSelector(
	[ getRSVPDetails ],
	( details ) => details.startDateMoment,
);

export const getRSVPStartTime = createSelector(
	[ getRSVPDetails ],
	( details ) => details.startTime,
);

export const getRSVPStartTimeNoSeconds = createSelector(
	[ getRSVPStartTime ],
	( startTime ) => startTime.slice( 0, -3 ),
);

export const getRSVPEndDate = createSelector(
	[ getRSVPDetails ],
	( details ) => details.endDate,
);

export const getRSVPEndDateInput = createSelector(
	[ getRSVPDetails ],
	( details ) => details.endDateInput,
);

export const getRSVPEndDateMoment = createSelector(
	[ getRSVPDetails ],
	( details ) => details.endDateMoment,
);

export const getRSVPEndTime = createSelector(
	[ getRSVPDetails ],
	( details ) => details.endTime,
);

export const getRSVPEndTimeNoSeconds = createSelector(
	[ getRSVPEndTime ],
	( endTime ) => endTime.slice( 0, -3 ),
);

export const getRSVPStartTimeInput = createSelector(
	[ getRSVPDetails ],
	( details ) => details.startTimeInput,
);

export const getRSVPEndTimeInput = createSelector(
	[ getRSVPDetails ],
	( details ) => details.endTimeInput,
);

/**
 * ------------------------------------------------------------
 * RSVP Temp Details
 * ------------------------------------------------------------
 */
export const getRSVPTempDetails = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.tempDetails,
);

export const getRSVPTempTitle = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.title,
);

export const getRSVPTempDescription = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.description,
);

export const getRSVPTempCapacity = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.capacity,
);

export const getRSVPTempNotGoingResponses = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.notGoingResponses,
);

export const getRSVPTempStartDate = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.startDate,
);

export const getRSVPTempStartDateInput = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.startDateInput,
);

export const getRSVPTempStartDateMoment = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.startDateMoment,
);

export const getRSVPTempStartTime = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.startTime,
);

export const getRSVPTempStartTimeNoSeconds = createSelector(
	[ getRSVPTempStartTime ],
	( startTime ) => startTime.slice( 0, -3 ),
);

export const getRSVPTempEndDate = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.endDate,
);

export const getRSVPTempEndDateInput = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.endDateInput,
);

export const getRSVPTempEndDateMoment = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.endDateMoment,
);

export const getRSVPTempEndTime = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.endTime,
);

export const getRSVPTempEndTimeNoSeconds = createSelector(
	[ getRSVPTempEndTime ],
	( endTime ) => endTime.slice( 0, -3 ),
);

export const getRSVPTempStartTimeInput = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.startTimeInput,
);

export const getRSVPTempEndTimeInput = createSelector(
	[ getRSVPTempDetails ],
	( tempDetails ) => tempDetails.endTimeInput,
);

/**
 * ------------------------------------------------------------
 * RSVP Header Image
 * ------------------------------------------------------------
 */
export const getRSVPHeaderImage = createSelector(
	[ getRSVPBlock ],
	( rsvp ) => rsvp.headerImage,
);

export const getRSVPHeaderImageId = createSelector(
	[ getRSVPHeaderImage ],
	( headerImage ) => headerImage.id,
);

export const getRSVPHeaderImageSrc = createSelector(
	[ getRSVPHeaderImage ],
	( headerImage ) => headerImage.src,
);

export const getRSVPHeaderImageAlt = createSelector(
	[ getRSVPHeaderImage ],
	( headerImage ) => headerImage.alt,
);
