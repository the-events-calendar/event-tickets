/* eslint-disable camelcase */
/**
 * Internal dependencies
 */
import * as types from '@moderntribe/tickets/data/shared/move/types';

export const DEFAULT_STATE = {
	post_type: 'all',
	search_terms: '',
	target_post_id: null,
};

export default function posts( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case types.SET_MODAL_DATA:
			return {
				...state,
				...action.payload,
			};
		case types.RESET_MODAL_DATA:
			return DEFAULT_STATE;
		default:
			return state;
	}
}

