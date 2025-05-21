/**
 * Internal dependencies
 */
import * as types from '../types';

export const DEFAULT_STATE = {
	isFetching: false,
	posts: {},
};

export default function posts( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case types.FETCH_POST_CHOICES:
			return {
				...state,
				isFetching: true,
			};
		case types.FETCH_POST_CHOICES_SUCCESS:
			return {
				...state,
				...action.data,
				isFetching: false,
			};
		case types.FETCH_POST_CHOICES_ERROR:
			return {
				...state,
				isFetching: false,
			};
		default:
			return state;
	}
}
