/**
 * Internal dependencies
 */
import * as types from '@moderntribe/tickets/data/blocks/ticket/types';
import tmp from './tmp';

export const DEFAULT_STATE = {
	sharedCapacity: '',
	tmp: {},
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_TOTAL_SHARED_CAPACITY:
			return {
				...state,
				sharedCapacity: action.payload.sharedCapacity,
			};
		case types.SET_TICKET_TMP_TICKET_SHARED_CAPACITY:
			return {
				...state,
				tmp: tmp( state.tmp, action ),
			};
		default:
			return state;
	}
};
