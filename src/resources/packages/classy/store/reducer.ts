import { Reducer, UnknownAction } from 'redux';
import { StoreState } from '../types/Store';

import {
	SET_TICKETS,
	SET_TICKETS_FOR_EVENT,
	SetTicketsAction,
	SetTicketsForEventAction,
} from '../types/Actions';

const initialState: StoreState = {
	allTickets: [], // Todo: remove this when not needed.
	tickets: [],
	isLoading: false,
	error: null,
};

export const reducer: Reducer<StoreState, UnknownAction> = ( state: StoreState = initialState, action ) => {
	switch ( action.type ) {
		case SET_TICKETS:
			return {
				...state,
				allTickets: ( action as SetTicketsAction ).tickets,
			};
		case SET_TICKETS_FOR_EVENT:
			return {
				...state,
				tickets: ( action as SetTicketsForEventAction ).tickets,
				currentEventId: ( action as SetTicketsForEventAction ).postId,
			};

		default:
			return state;
	}
};
