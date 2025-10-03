import { TicketSettings } from '@tec/tickets/classy/types/Ticket';
import { Reducer } from 'redux';
import { StoreState } from '../types/Store';
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
	let tickets: TicketSettings[];
	switch ( action.type ) {
		case CREATE_TICKET:
			const newTicket = ( action as CreateTicketAction ).ticket;
			tickets = state.tickets || [];
			return {
				...state,
				tickets: [ ...tickets, newTicket ],
			};
		case DELETE_TICKET:
			const ticketIdToDelete = ( action as DeleteTicketAction ).ticketId;
			tickets = state.tickets || [];
			return {
				...state,
				tickets: tickets.filter( ( ticket: TicketSettings ) => ticket.id !== ticketIdToDelete ),
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
			tickets = state.tickets || [];
			return {
				...state,
				tickets: tickets.map( ( ticket: TicketSettings ) =>
					ticket.id === ticketId ? { ...ticket, ticketData } : ticket
				),
			};

		default:
			return state;
	}
};
