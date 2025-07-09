import { Reducer, UnknownAction } from 'redux';
import { StoreState } from '../types/Store';

import {
	SET_LOADING,
	SET_TICKETS,
	SetLoadingAction,
	SetTicketsAction,
} from '../types/Actions';

const initialState: StoreState = {
	tickets: [],
	isLoading: false,
	error: null,
};

export const reducer: Reducer<StoreState, UnknownAction> = ( state: StoreState = initialState, action ) => {
	switch ( action.type ) {
		case SET_TICKETS:
			return {
				...state,
				tickets: ( action as SetTicketsAction ).tickets,
			};
		case SET_LOADING:
			return {
				...state,
				isLoading: ( action as SetLoadingAction ).isLoading,
			};

		default:
			return state;
	}
};
