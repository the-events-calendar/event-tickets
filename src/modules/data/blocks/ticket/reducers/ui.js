/**
 * Internal dependencies
 */
import * as types from './../types';

export const DEFAULT_STATE = {
	header: null,
	isSettingsOpen: false,
	isParentBlockSelected: false,
	isChildBlockSelected: false,
	isParentBlockLoading: false,
	activeChildBlockId: '',
	provider: '',
};

export default ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_TICKET_HEADER:
			return {
				...state,
				header: action.payload.header,
			};
		case types.SET_TICKET_SETTINGS_OPEN:
			return {
				...state,
				isSettingsOpen: action.payload.isSettingsOpen,
			};
		case types.SET_PARENT_BLOCK_SELECTED:
			return {
				...state,
				isParentBlockSelected: action.payload.selected,
			};
		case types.SET_CHILD_BLOCK_SELECTED:
			return {
				...state,
				isChildBlockSelected: action.payload.selected,
			};
		case types.SET_ACTIVE_CHILD_BLOCK_ID:
			return {
				...state,
				activeChildBlockId: action.payload.activeChildBlockId,
			};
		case types.SET_PARENT_BLOCK_LOADING:
			return {
				...state,
				isParentBlockLoading: action.payload.isParentBlockLoading,
			};
		case types.SET_PROVIDER:
			return {
				...state,
				provider: action.payload.provider,
			};
		default:
			return state;
	}
}
