/**
 * Internal dependencies
 */
import * as types from '@moderntribe/tickets/data/blocks/ticket/types';

export const DEFAULT_STATE = {
	sharedCapacity: '',
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_TMP_TICKET_SHARED_CAPACITY:
			return {
				...state,
				sharedCapacity: action.payload.sharedCapacity,
			};

		default:
			return state;
	}
};
