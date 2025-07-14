import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
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
		const body = new FormData();

		// Required fields
		if ( ticketData.eventId ) {
			body.append( 'post_id', ticketData.eventId.toString() );
		}
		if ( ticketData.provider ) {
			body.append( 'provider', ticketData.provider );
		}
		if ( ticketData.title ) {
			body.append( 'name', ticketData.title );
		}
		if ( ticketData.description ) {
			body.append( 'description', ticketData.description );
		}
		if ( ticketData.cost ) {
			body.append( 'price', ticketData.cost );
		}

		// Date and time fields
		if ( ticketData.availableFrom ) {
			// Extract date and time from availableFrom
			const availableFromDate = new Date( ticketData.availableFrom );
			const startDate = availableFromDate.toISOString().split( 'T' )[ 0 ];
			const startTime = availableFromDate.toTimeString().split( ' ' )[ 0 ];
			body.append( 'start_date', startDate );
			body.append( 'start_time', startTime );
		}

		if ( ticketData.availableUntil ) {
			// Extract date and time from availableUntil
			const availableUntilDate = new Date( ticketData.availableUntil );
			const endDate = availableUntilDate.toISOString().split( 'T' )[ 0 ];
			const endTime = availableUntilDate.toTimeString().split( ' ' )[ 0 ];
			body.append( 'end_date', endDate );
			body.append( 'end_time', endTime );
		}

		// Additional fields
		if ( ticketData.iac ) {
			body.append( 'iac', ticketData.iac );
		}

		// Capacity fields
		if ( ticketData.capacityDetails ) {
			const capacityType = ticketData.capacityDetails.globalStockMode;
			const capacity = ticketData.capacityDetails.max;

			// Map capacity type to ticket mode
			const isUnlimited = capacityType === 'own' || capacity === 0;
			body.append( 'ticket[mode]', isUnlimited ? '' : capacityType || '' );
			body.append( 'ticket[capacity]', isUnlimited ? '' : capacity?.toString() || '' );
		}

		// Sale price fields
		if ( ticketData.salePriceData ) {
			const salePriceData = ticketData.salePriceData;
			body.append( 'ticket[sale_price][checked]', salePriceData.enabled ? '1' : '0' );
			if ( salePriceData.salePrice ) {
				body.append( 'ticket[sale_price][price]', salePriceData.salePrice );
			}
			if ( salePriceData.startDate ) {
				body.append( 'ticket[sale_price][start_date]', salePriceData.startDate );
			}
			if ( salePriceData.endDate ) {
				body.append( 'ticket[sale_price][end_date]', salePriceData.endDate );
			}
		}

		// Menu order
		// todo: Replace this placeholder with actual logic.
		body.append( 'menu_order', '0' );

		/**
		 * Filter the body of the request before sending it to the API.
		 *
		 * @since TBD
		 *
		 * @param {Record<string, any>} body The object containing additional values to be sent in the request.
		 * @param {PartialTicket} ticketData The ticket data being sent.
		 */
		const additionalValues: Record<string, any> = applyFilters(
			'tec.tickets.classy.createTicket.body',
			{},
			ticketData
		);

		// Append/update additional values in the body.
		Object.entries( additionalValues ).forEach( ( [ key, value ] ) => {
			if ( value !== undefined && value !== null ) {
				if ( Array.isArray( value ) ) {
					value.forEach( ( item ) => body.append( key, item ) );
				} else {
					body.append( key, value );
				}
			}
		} );

		await apiFetch( {
			url: apiBaseUrl,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: body,
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
