import { Reducer, UnknownAction } from 'redux';
import { StoreState } from '../types/Store';

import {
	SET_CURRENT_POST_ID,
	SET_TICKETS,
	SET_TICKETS_FOR_EVENT,
	SetCurrentPostIdAction,
	SetTicketsAction,
	SetTicketsForEventAction,
} from '../types/Actions';

const initialState: StoreState = {
	tickets: [],
	currentEventId: null,
	isLoading: false,
	error: null,
};

export const reducer: Reducer<StoreState, ActionTypes> = ( state = initialState, action ) => {
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
		case SET_CURRENT_POST_ID:
			return {
				...state,
				currentEventId: ( action as SetCurrentPostIdAction ).postId,
			};

		default:
			return state;
	}
};
