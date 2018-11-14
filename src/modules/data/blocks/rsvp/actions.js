/**
 * Internal dependencies
 */
import { types } from '@moderntribe/tickets/data/blocks/rsvp';

export const createRSVP = () => ( {
	type: types.CREATE_RSVP,
} );

export const initializeRSVP = () => ( {
	type: types.INITIALIZE_RSVP,
} );

export const deleteRSVP = () => ( {
	type: types.DELETE_RSVP,
} );

export const setRSVPId = ( id ) => ( {
	type: types.SET_RSVP_ID,
	payload: {
		id,
	},
} );

export const setRSVPSettingsOpen = ( settingsOpen ) => ( {
	type: types.SET_RSVP_SETTINGS_OPEN,
	payload: {
		settingsOpen,
	},
} );

export const setRSVPHasChanges = ( hasChanges ) => ( {
	type: types.SET_RSVP_HAS_CHANGES,
	payload: {
		hasChanges,
	},
} );

export const setRSVPIsLoading = ( isLoading ) => ( {
	type: types.SET_RSVP_IS_LOADING,
	payload: {
		isLoading,
	},
} );

export const setRSVPIsSettingsLoading = ( isSettingsLoading ) => ( {
	type: types.SET_RSVP_IS_SETTINGS_LOADING,
	payload: {
		isSettingsLoading,
	},
} );

export const setRSVPGoingCount = ( goingCount ) => ( {
	type: types.SET_RSVP_GOING_COUNT,
	payload: {
		goingCount,
	},
} );

export const setRSVPNotGoingCount = ( notGoingCount ) => ( {
	type: types.SET_RSVP_NOT_GOING_COUNT,
	payload: {
		notGoingCount,
	},
} );

export const setRSVPTitle = ( title ) => ( {
	type: types.SET_RSVP_TITLE,
	payload: {
		title,
	},
} );

export const setRSVPDescription = ( description ) => ( {
	type: types.SET_RSVP_DESCRIPTION,
	payload: {
		description,
	},
} );

export const setRSVPCapacity = ( capacity ) => ( {
	type: types.SET_RSVP_CAPACITY,
	payload: {
		capacity,
	},
} );

export const setRSVPNotGoingResponses = ( notGoingResponses ) => ( {
	type: types.SET_RSVP_NOT_GOING_RESPONSES,
	payload: {
		notGoingResponses,
	},
} );

export const setRSVPStartDate = ( startDate ) => ( {
	type: types.SET_RSVP_START_DATE,
	payload: {
		startDate,
	},
} );

export const setRSVPStartDateObj = ( startDateObj ) => ( {
	type: types.SET_RSVP_START_DATE_OBJ,
	payload: {
		startDateObj,
	},
} );

export const setRSVPEndDate = ( endDate ) => ( {
	type: types.SET_RSVP_END_DATE,
	payload: {
		endDate,
	},
} );

export const setRSVPEndDateObj = ( endDateObj ) => ( {
	type: types.SET_RSVP_END_DATE_OBJ,
	payload: {
		endDateObj,
	},
} );

export const setRSVPStartTime = ( startTime ) => ( {
	type: types.SET_RSVP_START_TIME,
	payload: {
		startTime,
	},
} );

export const setRSVPEndTime = ( endTime ) => ( {
	type: types.SET_RSVP_END_TIME,
	payload: {
		endTime,
	},
} );

export const setRSVPTempTitle = ( title ) => ( {
	type: types.SET_RSVP_TEMP_TITLE,
	payload: {
		title,
	},
} );

export const setRSVPTempDescription = ( description ) => ( {
	type: types.SET_RSVP_TEMP_DESCRIPTION,
	payload: {
		description,
	},
} );

export const setRSVPTempCapacity = ( capacity ) => ( {
	type: types.SET_RSVP_TEMP_CAPACITY,
	payload: {
		capacity,
	},
} );

export const setRSVPTempNotGoingResponses = ( notGoingResponses ) => ( {
	type: types.SET_RSVP_TEMP_NOT_GOING_RESPONSES,
	payload: {
		notGoingResponses,
	},
} );

export const setRSVPTempStartDate = ( startDate ) => ( {
	type: types.SET_RSVP_TEMP_START_DATE,
	payload: {
		startDate,
	},
} );

export const setRSVPTempStartDateObj = ( startDateObj ) => ( {
	type: types.SET_RSVP_TEMP_START_DATE_OBJ,
	payload: {
		startDateObj,
	},
} );

export const setRSVPTempEndDate = ( endDate ) => ( {
	type: types.SET_RSVP_TEMP_END_DATE,
	payload: {
		endDate,
	},
} );

export const setRSVPTempEndDateObj = ( endDateObj ) => ( {
	type: types.SET_RSVP_TEMP_END_DATE_OBJ,
	payload: {
		endDateObj,
	},
} );

export const setRSVPTempStartTime = ( startTime ) => ( {
	type: types.SET_RSVP_TEMP_START_TIME,
	payload: {
		startTime,
	},
} );

export const setRSVPTempEndTime = ( endTime ) => ( {
	type: types.SET_RSVP_TEMP_END_TIME,
	payload: {
		endTime,
	},
} );

export const setRSVPHeaderImage = ( payload ) => ( {
	type: types.SET_RSVP_HEADER_IMAGE,
	payload,
} );

export const setRSVPDetails = ( payload ) => ( {
	type: types.SET_RSVP_DETAILS,
	payload,
} );

export const setRSVPTempDetails = ( payload ) => ( {
	type: types.SET_RSVP_TEMP_DETAILS,
	payload,
} );
