import { StoreState } from '../types/Store';
import { Ticket } from '../types/Ticket';


/**
 * Returns the tickets from the store state.
 *
 * @since TBD
 *
 * @param {StoreState} state The current store state.
 * @return {Ticket[]} The list of tickets.
 */
export const getTickets = ( state: StoreState ): Ticket[] => {
	return state?.tickets || [];
}

/**
 * Returns a specific ticket by its ID from the store state.
 *
 * @since TBD
 *
 * @param {StoreState} state The current store state.
 */
export const getTicketById = ( state: StoreState ) => {
	return ( ticketId: number ): Ticket | undefined => {
		return state?.tickets?.find( ticket => ticket.id === ticketId );
	};
}

/**
 * Checks if the store is currently loading.
 *
 * @since TBD
 *
 * @param {StoreState} state The current store state.
 * @return {boolean} True if loading, false otherwise.
 */
export const isLoading = ( state: StoreState ): boolean => {
	return state?.loading || false;
}
