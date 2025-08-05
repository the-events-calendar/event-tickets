import { TicketSettings } from '../types/Ticket';
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
import { StoreDispatch } from '../types/Store';

/**
 * Adds a new ticket to the store.
 *
 * @since TBD
 *
 * @param {TicketSettings} ticket The ticket to add to the store.
 */
const addTicket = ( ticket: TicketSettings ): CreateTicketAction => ( {
	type: CREATE_TICKET,
	ticket
} );

/**
 * Deletes a ticket from the store by its ID.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to delete.
 */
const deleteTicket = ( ticketId: number ): DeleteTicketAction => ( {
	type: DELETE_TICKET,
	ticketId,
} );

/**
 * Sets the loading state in the store.
 *
 * @since TBD
 *
 * @param {boolean} isLoading The loading state to set in the store.
 */
const setIsLoading = ( isLoading: boolean ): SetIsLoadingAction => ( {
	type: SET_IS_LOADING,
	isLoading
} );

/**
 * Sets the tickets in the store.
 *
 * @since TBD
 *
 * @param {TicketSettings[]} tickets The list of tickets to set in the store.
 */
const setTickets = ( tickets: TicketSettings[] ): SetTicketsAction => ( {
	type: SET_TICKETS,
	tickets
} );

/**
 * Updates a ticket in the store by its ID.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to update.
 * @param {TicketSettings} ticketData The data to update the ticket with.
 */
const updateTicket = ( ticketId: number, ticketData: TicketSettings ): UpdateTicketAction => ( {
	type: UPDATE_TICKET,
	ticketId,
	ticketData,
} );

export const actions: StoreDispatch = {
	addTicket,
	deleteTicket,
	setIsLoading,
	setTickets,
	updateTicket,
}
