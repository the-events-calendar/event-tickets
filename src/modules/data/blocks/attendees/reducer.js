/**
 * Internal dependencies
 */
import * as types from './types';

export const DEFAULT_STATE = {
	title: '',
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_ATTENDEES_TITLE:
			return {
				...state,
				title: action.payload.title,
			};
		default:
			return state;
	}
};
