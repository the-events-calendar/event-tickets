import { Reducer } from 'redux';
import { StoreState } from '../types/Store';
import { hydrateTicket } from '../functions/tickets';
import {
	CREATE_TICKET,
	DELETE_TICKET,
	SET_IS_LOADING,
	SET_TICKETS,
	UPDATE_TICKET,
	CreateTicketAction,
	DeleteTicketAction,
	SetIsLoadingAction,
	SetTicketsAction,
	UpdateTicketAction,
} from '../types/Actions';

const initialState: StoreState = {
	tickets: null,
	loading: true,
};

export const reducer: Reducer<StoreState> = ( state: StoreState = initialState, action ) => {
	switch ( action.type ) {
		case CREATE_TICKET:
			const newTicket = ( action as CreateTicketAction ).ticket;
			return {
				...state,
				tickets: [ ...state.tickets, hydrateTicket( newTicket ) ],
			};
		case DELETE_TICKET:
			const ticketIdToDelete = ( action as DeleteTicketAction ).ticketId;
			return {
				...state,
				tickets: state.tickets.filter( ticket => ticket.id !== ticketIdToDelete ),
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
				tickets: state.tickets.map( ticket =>
					ticket.id === ticketId ? { ...ticket, ...hydrateTicket( ticketData ) } : ticket
				),
			};

		default:
			return state;
	}
};
