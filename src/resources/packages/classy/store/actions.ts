import { TicketSettings } from '../types/Ticket';
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
	ticket,
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
 * Action creator function to set the capacity for an event.
 *
 * @since TBD
 *
 * @param {number} capacity The maximum capacity allowed for the event.
 * @returns {SetEventCapacityAction} An action object containing the type and updated capacity.
 */
const setEventCapacity = ( capacity: number ): SetEventCapacityAction => ( {
	type: SET_EVENT_CAPACITY,
	capacity,
} );

/**
 * Sets whether the event has shared capacity in the store.
 *
 * @since TBD
 *
 * @param {boolean} hasSharedCapacity Indicates if the event has shared capacity.
 */
const setEventHasSharedCapacity = ( hasSharedCapacity: boolean ): SetEventHasSharedCapacityAction => ( {
	type: SET_EVENT_HAS_SHARED_CAPACITY,
	hasSharedCapacity,
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
	isLoading,
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
	tickets,
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
	setEventCapacity,
	setEventHasSharedCapacity,
	setIsLoading,
	setTickets,
	updateTicket,
};
