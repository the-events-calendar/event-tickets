/**
 * Internal dependencies
 */
import * as types from '../types';

export const DEFAULT_STATE = {
	showModal: false,
};

export default function ui( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case types.SHOW_MODAL:
			return {
				...state,
				showModal: true,
			};
		case types.HIDE_MODAL:
			return {
				...state,
				showModal: false,
			};
		default:
			return state;
	}
}
