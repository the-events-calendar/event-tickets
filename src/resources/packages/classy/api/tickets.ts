import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { PartialTicket, Ticket } from '../types/Ticket';
import { GetTicketApiResponse, GetTicketsApiResponse, TicketsApiParams } from '../types/Api';

const apiBaseUrl = '/tribe/tickets/v1/tickets';

/**
 * Fetch tickets from the API.
 *
 * @since TBD
 *
 * @param {TicketsApiParams} params Optional parameters for the API request.
 * @return {Promise<GetTicketsApiResponse>} A promise that resolves to the tickets response.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const fetchTickets = async ( params: TicketsApiParams = {} ): Promise<GetTicketsApiResponse> => {
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

	return new Promise<GetTicketsApiResponse>( async ( resolve, reject ) => {
		await apiFetch( {
			path: path,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( ( data ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject( new Error( 'Failed to fetch tickets: response did not return an object.' ) );
				} else if ( ! ( data.hasOwnProperty( 'tickets' ) && data.hasOwnProperty( 'total' ) ) ) {
					reject( new Error( 'Tickets fetch request did not return an object with tickets and total properties.' ) );
				} else {
					resolve( data as GetTicketsApiResponse );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to fetch tickets: ${ error.message }` ) );
			} );
	} );
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
	return new Promise<Ticket[]>( async ( resolve, reject ) => {
		// todo: Handle the potential for multiple pages of results.
		await fetchTickets( { include_post: [ postId ] } )
			.then( ( response: GetTicketsApiResponse ) => {
				resolve( response.tickets );
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to fetch tickets for post ID ${ postId }: ${ error.message }` ) );
			} );
	} );
};

/**
 * Create a new ticket.
 *
 * @since TBD
 *
 * @param {PartialTicket} ticketData The data for the new ticket.
 * @return {Promise<GetTicketApiResponse>} A promise that resolves to the created ticket.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const createTicket = async ( ticketData: PartialTicket ): Promise<GetTicketApiResponse> => {
	return new Promise<GetTicketApiResponse>( async ( resolve, reject ) => {
		// todo: use proper form body structure for the request.
		await apiFetch( {
			url: apiBaseUrl,
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			data: ticketData,
		} )
			.then( ( data ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject( new Error( 'Failed to create ticket: response did not return an object.' ) );
				} else {
					resolve( data as GetTicketApiResponse );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to create ticket: ${ error.message }` ) );
			} );
	} );
};

/**
 * Update an existing ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to update.
 * @param {PartialTicket} ticketData The data to update the ticket with.
 * @return {Promise<GetTicketApiResponse>} A promise that resolves to the updated ticket.
 * @throws {Error} If the response is not an object or does not contain the expected properties.
 */
export const updateTicket = async ( ticketId: number, ticketData: PartialTicket ): Promise<GetTicketApiResponse> => {
	return new Promise<GetTicketApiResponse>( async ( resolve, reject ) => {
		await apiFetch( {
			path: `${ apiBaseUrl }/${ ticketId }`,
			method: 'PUT',
			headers: {
				'Content-Type': 'application/json',
			},
			data: ticketData,
		} )
			.then( ( data ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject( new Error( 'Failed to update ticket: response did not return an object.' ) );
				} else {
					resolve( data as GetTicketApiResponse );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to update ticket: ${ error.message }` ) );
			} );
	} );
};

/**
 * Delete a ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId The ID of the ticket to delete.
 * @return {Promise<void>} A promise that resolves when the ticket is deleted.
 * @throws {Error} If the deletion fails.
 */
export const deleteTicket = async ( ticketId: number ): Promise<void> => {
	return new Promise<void>( async ( resolve, reject ) => {
		await apiFetch( {
			path: `${ apiBaseUrl }/${ ticketId }`,
			method: 'DELETE',
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( () => {
				resolve();
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to delete ticket: ${ error.message }` ) );
			} );
	} );
};
