/**
 * Internal dependencies
 */
import * as types from './types';

export const DEFAULT_STATE = {
	title: '',
	displayTitle: true,
	displaySubtitle: true,
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_ATTENDEES_TITLE:
			return {
				...state,
				title: action.payload.title,
			};
		case types.SET_ATTENDEES_DISPLAY_TITLE:
			return {
				...state,
				displayTitle: action.payload.displayTitle,
			};
		case types.SET_ATTENDEES_DISPLAY_SUBTITLE:
			return {
				...state,
				displaySubtitle: action.payload.displaySubtitle,
			};
		default:
			return state;
	}
};
