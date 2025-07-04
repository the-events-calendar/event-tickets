import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Ticket, PartialTicket } from '../types/Ticket';
import { TicketsApiParams, TicketsApiResponse } from "../types/Api";

const apiBaseUrl = '/tribe/tickets/v1/tickets';

/**
 * Fetch tickets from the API.
 *
 * @since TBD
 *
 * @param {TicketsApiParams} params Optional parameters for the API request.
 * @return {Promise<TicketsApiResponse>} A promise that resolves to the tickets response.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const fetchTickets = async ( params: TicketsApiParams = {} ): Promise<TicketsApiResponse> => {
	const searchParams = new URLSearchParams();

	if ( params.include_post ) {
		params.include_post.forEach( ( postId ) => {
			searchParams.append( 'include_post', postId.toString() );
		} );
	}

	if ( params.per_page ) {
		searchParams.set( 'per_page', params.per_page.toString() );
	}

	if ( params.page ) {
		searchParams.set( 'page', params.page.toString() );
	}

	const path = addQueryArgs( apiBaseUrl, searchParams );
	console.log( `Fetching tickets from: ${ path }` );

	const response: TicketsApiResponse = await apiFetch( {
		path: path,
		headers: {
			'Content-Type': 'application/json',
		},
	} );

	// Check that the response is an object.
	if ( ! ( response && typeof response === 'object' ) ) {
		throw new Error( 'Failed to fetch tickets: response did not return an object.' );
	}

	// Check that the response has a 'tickets' property.
	if ( ! ( response.hasOwnProperty( 'tickets' ) && response.hasOwnProperty( 'total' ) ) ) {
		throw new Error( 'Tickets fetch request did not return an object with tickets and total properties.' );
	}

	return response as TicketsApiResponse;
};

/**
 * Fetch tickets for a specific post ID.
 *
 * @since TBD
 *
 * @param {number} postId The ID of the post to fetch tickets for.
 * @return {Promise<Ticket[]>} A promise that resolves to an array of tickets.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const fetchTicketsForPost = async ( postId: number ): Promise<Ticket[]> => {
	const response = await fetchTickets( { include_post: [ postId ] } );
	return response.tickets;
};

/**
 * Create a new ticket.
 *
 * @since TBD
 *
 * @param {PartialTicket} ticketData The data for the new ticket.
 * @return {Promise<Ticket>} The created ticket.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const createTicket = async ( ticketData: PartialTicket ): Promise<Ticket> => {
	const response: Ticket = await apiFetch( {
		url: apiBaseUrl,
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		data: ticketData,
	} );

	// Check that the response is an object.
	if ( ! ( response && typeof response === 'object' ) ) {
		throw new Error( 'Failed to create ticket: response did not return an object.' );
	}

	return response as Ticket;
};

/**
 * Update an existing ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to update.
 * @param {PartialTicket} ticketData The data to update the ticket with.
 * @return {Promise<Ticket>} The updated ticket.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const updateTicket = async ( ticketId: number, ticketData: PartialTicket ): Promise<Ticket> => {
	const response: Ticket = await apiFetch( {
		path: `${ apiBaseUrl }/${ ticketId }`,
		method: 'PUT',
		headers: {
			'Content-Type': 'application/json',
		},
		data: ticketData,
	} );

	// Check that the response is an object.
	if ( ! ( response && typeof response === 'object' ) ) {
		throw new Error( 'Failed to update ticket: response did not return an object.' );
	}

	return response as Ticket;
};

/**
 * Delete a ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to delete.
 * @return {Promise<void>} A promise that resolves when the ticket is deleted.
 */
export const deleteTicket = async ( ticketId: number ): Promise<void> => {
	return apiFetch( {
		path: `${ apiBaseUrl }/${ ticketId }`,
		method: 'DELETE',
		headers: {
			'Content-Type': 'application/json',
		},
	} );
};
