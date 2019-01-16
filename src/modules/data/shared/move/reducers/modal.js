/* eslint-disable camelcase */
/**
 * Internal dependencies
 */
import * as types from '@moderntribe/tickets/data/shared/move/types';

export const DEFAULT_STATE = {
	post_type: 'all',
	search_terms: '',
	target_post_id: null,
	ticketId: null,
	clientId: null,
	isSubmitting: false,
};

export default function modal( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case types.SET_MODAL_DATA:
			return {
				...state,
				...action.payload,
			};

		case types.MOVE_TICKET:
			return {
				...state,
				isSubmitting: true,
			};

		case types.MOVE_TICKET_ERROR:
		case types.MOVE_TICKET_SUCCESS:
			return {
				...state,
				isSubmitting: false,
			};

		case types.RESET_MODAL_DATA:
			return DEFAULT_STATE;
		default:
			return state;
	}
}

