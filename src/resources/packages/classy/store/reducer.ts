import { Reducer } from 'redux';
import { StoreState } from '../types/Store';
import { hydrateTicket } from '../functions/tickets';
import {
	CREATE_TICKET,
	DELETE_TICKET,
	SET_EVENT_CAPACITY,
	SET_EVENT_HAS_SHARED_CAPACITY,
	SET_IS_LOADING,
	SET_TICKETS,
	UPDATE_TICKET,
	CreateTicketAction,
	DeleteTicketAction,
	SetEventCapacityAction,
	SetEventHasSharedCapacityAction,
	SetIsLoadingAction,
	SetTicketsAction,
	UpdateTicketAction,
} from '../types/Actions';

const initialState: StoreState = {
	eventCapacity: undefined,
	eventHasSharedCapacity: false,
	loading: true,
	tickets: null,
};

export const reducer: Reducer< StoreState > = ( state: StoreState = initialState, action ) => {
	switch ( action.type ) {
		case CREATE_TICKET:
			const newTicket = ( action as CreateTicketAction ).ticket;
			return {
				...state,
				tickets: [ ...state.tickets, newTicket ],
			};
		case DELETE_TICKET:
			const ticketIdToDelete = ( action as DeleteTicketAction ).ticketId;
			return {
				...state,
				tickets: state.tickets.filter( ( ticket ) => ticket.id !== ticketIdToDelete ),
			};
		case SET_EVENT_CAPACITY:
			const capacity = ( action as SetEventCapacityAction ).capacity;
			return {
				...state,
				eventCapacity: capacity,
			};
		case SET_EVENT_HAS_SHARED_CAPACITY:
			const hasSharedCapacity = ( action as SetEventHasSharedCapacityAction ).hasSharedCapacity;
			return {
				...state,
				eventHasSharedCapacity: hasSharedCapacity,
			};
		case SET_IS_LOADING:
			const isLoading = ( action as SetIsLoadingAction ).isLoading;
			return {
				...state,
				loading: isLoading,
			};
		case SET_TICKETS:
			return {
				...state,
				tickets: ( action as SetTicketsAction ).tickets,
			};
		case UPDATE_TICKET:
			const { ticketId, ticketData } = action as UpdateTicketAction;
			return {
				...state,
				tickets: state.tickets.map( ( ticket ) =>
					ticket.id === ticketId ? { ...ticket, ...hydrateTicket( ticketData ) } : ticket
				),
			};

		default:
			return state;
	}
};
