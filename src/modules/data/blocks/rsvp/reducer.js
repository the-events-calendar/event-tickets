/**
 * Internal dependencies
 */
import details, {
	DEFAULT_STATE as DETAILS_DEFAULT_STATE,
} from './reducers/details';
import tempDetails from './reducers/temp-details';
import headerImage, {
	DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE,
} from './reducers/header-image';
import { types } from '@moderntribe/tickets/data/blocks/rsvp';

export const DEFAULT_STATE = {
	id: 0,
	created: false,
	settingsOpen: false,
	hasChanges: false,
	isLoading: false,
	isSettingsLoading: false,
	isModalOpen: false,
	goingCount: 0,
	notGoingCount: 0,
	hasAttendeeInfoFields: false,
	details: DETAILS_DEFAULT_STATE,
	tempDetails: DETAILS_DEFAULT_STATE,
	headerImage: HEADER_IMAGE_DEFAULT_STATE,
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.CREATE_RSVP:
			return {
				...state,
				created: true,
			};
		case types.DELETE_RSVP:
			return DEFAULT_STATE;
		case types.SET_RSVP_ID:
			return {
				...state,
				id: action.payload.id,
			};
		case types.SET_RSVP_SETTINGS_OPEN:
			return {
				...state,
				settingsOpen: action.payload.settingsOpen,
			};
		case types.SET_RSVP_HAS_CHANGES:
			return {
				...state,
				hasChanges: action.payload.hasChanges,
			};
		case types.SET_RSVP_IS_LOADING:
			return {
				...state,
				isLoading: action.payload.isLoading,
			};
		case types.SET_RSVP_IS_SETTINGS_LOADING:
			return {
				...state,
				isSettingsLoading: action.payload.isSettingsLoading,
			};
		case types.SET_RSVP_IS_MODAL_OPEN:
			return {
				...state,
				isModalOpen: action.payload.isModalOpen,
			};
		case types.SET_RSVP_GOING_COUNT:
			return {
				...state,
				goingCount: action.payload.goingCount,
			};
		case types.SET_RSVP_NOT_GOING_COUNT:
			return {
				...state,
				notGoingCount: action.payload.notGoingCount,
			};
		case types.SET_RSVP_HAS_ATTENDEE_INFO_FIELDS:
			return {
				...state,
				hasAttendeeInfoFields: action.payload.hasAttendeeInfoFields,
			};
		case types.SET_RSVP_HAS_DURATION_ERROR:
			return {
				...state,
				hasDurationError: action.payload.hasDurationError,
			};
		case types.SET_RSVP_TITLE:
		case types.SET_RSVP_DESCRIPTION:
		case types.SET_RSVP_CAPACITY:
		case types.SET_RSVP_NOT_GOING_RESPONSES:
		case types.SET_RSVP_START_DATE:
		case types.SET_RSVP_START_DATE_INPUT:
		case types.SET_RSVP_START_DATE_MOMENT:
		case types.SET_RSVP_END_DATE:
		case types.SET_RSVP_END_DATE_INPUT:
		case types.SET_RSVP_END_DATE_MOMENT:
		case types.SET_RSVP_START_TIME:
		case types.SET_RSVP_END_TIME:
		case types.SET_RSVP_START_TIME_INPUT:
		case types.SET_RSVP_END_TIME_INPUT:
			return {
				...state,
				details: details( state.details, action ),
			};
		case types.SET_RSVP_TEMP_TITLE:
		case types.SET_RSVP_TEMP_DESCRIPTION:
		case types.SET_RSVP_TEMP_CAPACITY:
		case types.SET_RSVP_TEMP_NOT_GOING_RESPONSES:
		case types.SET_RSVP_TEMP_START_DATE:
		case types.SET_RSVP_TEMP_START_DATE_INPUT:
		case types.SET_RSVP_TEMP_START_DATE_MOMENT:
		case types.SET_RSVP_TEMP_END_DATE:
		case types.SET_RSVP_TEMP_END_DATE_INPUT:
		case types.SET_RSVP_TEMP_END_DATE_MOMENT:
		case types.SET_RSVP_TEMP_START_TIME:
		case types.SET_RSVP_TEMP_END_TIME:
		case types.SET_RSVP_TEMP_START_TIME_INPUT:
		case types.SET_RSVP_TEMP_END_TIME_INPUT:
			return {
				...state,
				tempDetails: tempDetails( state.tempDetails, action ),
			};
		case types.SET_RSVP_HEADER_IMAGE:
			return {
				...state,
				headerImage: headerImage( state.headerImage, action ),
			};
		default:
			return state;
	}
};
