/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './details';
import { types } from '@moderntribe/tickets/data/blocks/rsvp';

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_RSVP_TEMP_TITLE:
			return {
				...state,
				title: action.payload.title,
			};
		case types.SET_RSVP_TEMP_DESCRIPTION:
			return {
				...state,
				description: action.payload.description,
			};
		case types.SET_RSVP_TEMP_CAPACITY:
			return {
				...state,
				capacity: action.payload.capacity,
			};
		case types.SET_RSVP_TEMP_NOT_GOING_RESPONSES:
			return {
				...state,
				notGoingResponses: action.payload.notGoingResponses,
			};
		case types.SET_RSVP_TEMP_START_DATE:
			return {
				...state,
				startDate: action.payload.startDate,
			};
		case types.SET_RSVP_TEMP_START_DATE_OBJ:
			return {
				...state,
				startDateObj: action.payload.startDateObj,
			};
		case types.SET_RSVP_TEMP_END_DATE:
			return {
				...state,
				endDate: action.payload.endDate,
			};
		case types.SET_RSVP_TEMP_END_DATE_OBJ:
			return {
				...state,
				endDateObj: action.payload.endDateObj,
			};
		case types.SET_RSVP_TEMP_START_TIME:
			return {
				...state,
				startTime: action.payload.startTime,
			};
		case types.SET_RSVP_TEMP_END_TIME:
			return {
				...state,
				endTime: action.payload.endTime,
			};
		default:
			return state;
	}
};
