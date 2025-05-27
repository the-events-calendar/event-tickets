/**
 * Internal dependencies
 */
import { types } from './index';

//
// ─── RSVP ACTIONS ───────────────────────────────────────────────────────────────
//

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

export const setRSVPIsAddEditOpen = ( isAddEditOpen ) => ( {
	type: types.SET_RSVP_IS_ADD_EDIT_OPEN,
	payload: {
		isAddEditOpen,
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

export const setRSVPIsModalOpen = ( isModalOpen ) => ( {
	type: types.SET_RSVP_IS_MODAL_OPEN,
	payload: {
		isModalOpen,
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

export const setRSVPHasAttendeeInfoFields = ( hasAttendeeInfoFields ) => ( {
	type: types.SET_RSVP_HAS_ATTENDEE_INFO_FIELDS,
	payload: {
		hasAttendeeInfoFields,
	},
} );

export const setRSVPHasDurationError = ( hasDurationError ) => ( {
	type: types.SET_RSVP_HAS_DURATION_ERROR,
	payload: {
		hasDurationError,
	},
} );

export const setRSVPDetails = ( payload ) => ( {
	type: types.SET_RSVP_DETAILS,
	payload,
} );

export const setRSVPTempDetails = ( payload ) => ( {
	type: types.SET_RSVP_TEMP_DETAILS,
	payload,
} );

export const setRSVPHeaderImage = ( payload ) => ( {
	type: types.SET_RSVP_HEADER_IMAGE,
	payload,
} );

//
// ─── RSVP DETAILS ACTIONS ───────────────────────────────────────────────────────
//

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

export const setRSVPStartDateInput = ( startDateInput ) => ( {
	type: types.SET_RSVP_START_DATE_INPUT,
	payload: {
		startDateInput,
	},
} );

export const setRSVPStartDateMoment = ( startDateMoment ) => ( {
	type: types.SET_RSVP_START_DATE_MOMENT,
	payload: {
		startDateMoment,
	},
} );

export const setRSVPEndDate = ( endDate ) => ( {
	type: types.SET_RSVP_END_DATE,
	payload: {
		endDate,
	},
} );

export const setRSVPEndDateInput = ( endDateInput ) => ( {
	type: types.SET_RSVP_END_DATE_INPUT,
	payload: {
		endDateInput,
	},
} );

export const setRSVPEndDateMoment = ( endDateMoment ) => ( {
	type: types.SET_RSVP_END_DATE_MOMENT,
	payload: {
		endDateMoment,
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

export const setRSVPStartTimeInput = ( startTimeInput ) => ( {
	type: types.SET_RSVP_START_TIME_INPUT,
	payload: {
		startTimeInput,
	},
} );

export const setRSVPEndTimeInput = ( endTimeInput ) => ( {
	type: types.SET_RSVP_END_TIME_INPUT,
	payload: {
		endTimeInput,
	},
} );

//
// ─── RSVP TEMP DETAILS ACTIONS ──────────────────────────────────────────────────
//

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

export const setRSVPTempStartDateInput = ( startDateInput ) => ( {
	type: types.SET_RSVP_TEMP_START_DATE_INPUT,
	payload: {
		startDateInput,
	},
} );

export const setRSVPTempStartDateMoment = ( startDateMoment ) => ( {
	type: types.SET_RSVP_TEMP_START_DATE_MOMENT,
	payload: {
		startDateMoment,
	},
} );

export const setRSVPTempEndDate = ( endDate ) => ( {
	type: types.SET_RSVP_TEMP_END_DATE,
	payload: {
		endDate,
	},
} );

export const setRSVPTempEndDateInput = ( endDateInput ) => ( {
	type: types.SET_RSVP_TEMP_END_DATE_INPUT,
	payload: {
		endDateInput,
	},
} );

export const setRSVPTempEndDateMoment = ( endDateMoment ) => ( {
	type: types.SET_RSVP_TEMP_END_DATE_MOMENT,
	payload: {
		endDateMoment,
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

export const setRSVPTempStartTimeInput = ( startTimeInput ) => ( {
	type: types.SET_RSVP_TEMP_START_TIME_INPUT,
	payload: {
		startTimeInput,
	},
} );

export const setRSVPTempEndTimeInput = ( endTimeInput ) => ( {
	type: types.SET_RSVP_TEMP_END_TIME_INPUT,
	payload: {
		endTimeInput,
	},
} );

//
// ─── RSVP THUNK & SAGA ACTIONS ──────────────────────────────────────────────────
//

export const createRSVP = () => ( {
	type: types.CREATE_RSVP,
} );

export const initializeRSVP = () => ( {
	type: types.INITIALIZE_RSVP,
} );

export const deleteRSVP = () => ( {
	type: types.DELETE_RSVP,
} );

export const handleRSVPStartDate = ( payload ) => ( {
	type: types.HANDLE_RSVP_START_DATE,
	payload,
} );

export const handleRSVPEndDate = ( payload ) => ( {
	type: types.HANDLE_RSVP_END_DATE,
	payload,
} );

export const handleRSVPStartTime = ( seconds ) => ( {
	type: types.HANDLE_RSVP_START_TIME,
	payload: {
		seconds,
	},
} );

export const handleRSVPEndTime = ( seconds ) => ( {
	type: types.HANDLE_RSVP_END_TIME,
	payload: {
		seconds,
	},
} );

export const fetchRSVPHeaderImage = ( id ) => ( {
	type: types.FETCH_RSVP_HEADER_IMAGE,
	payload: {
		id,
	},
} );

export const updateRSVPHeaderImage = ( image ) => ( {
	type: types.UPDATE_RSVP_HEADER_IMAGE,
	payload: {
		image,
	},
} );

export const deleteRSVPHeaderImage = () => ( {
	type: types.DELETE_RSVP_HEADER_IMAGE,
} );
