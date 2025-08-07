import { StoreState } from '../types/Store';
import { TicketSettings } from '../types/Ticket';

/**
 * Determines whether the event has shared capacity from the application's state.
 *
 * @since TBD
 *
 * @param {StoreState} state The current state of the application.
 * @returns {boolean} Returns true if the event has shared capacity, otherwise false.
 */
export const getEventHasSharedCapacity = ( state: StoreState ): boolean => {
	return state?.eventHasSharedCapacity || false;
}

/**
 * Retrieves the event capacity from the given application state.
 *
 * @since TBD
 *
 * @param {StoreState} state The state object representing the application's store state.
 * @returns {number|undefined} The event capacity if available, otherwise undefined.
 */
export const getEventCapacity = ( state: StoreState ): number | undefined => {
	return state?.eventCapacity;
}

/**
 * Returns the tickets from the store state.
 *
 * @since TBD
 *
 * @param {StoreState} state The current store state.
 * @return {TicketSettings[]} The list of tickets.
 */
export const getTickets = ( state: StoreState ): TicketSettings[] => {
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
	return ( ticketId: number ): TicketSettings | undefined => {
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
