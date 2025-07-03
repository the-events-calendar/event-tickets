import { StoreState, StoreSelectors } from '../types/Store';
import { Ticket } from '../types/Ticket';



const getTickets = ( state: StoreState ) => {
	return state?.tickets || [];
}

const getIsLoading = ( state: StoreState ) => {
	return state?.isLoading || false;
}

const getError = ( state: StoreState ) => {
	return state?.error || null;
}

const getTicketById = ( state: StoreState ) => {
	return ( ticketId: number ): Ticket | undefined => {
		return state?.tickets?.find( ticket => ticket.id === ticketId );
	};
}

export const selectors: StoreSelectors = {
	getTickets,
	getIsLoading,
	getError,
	getTicketById,
}
