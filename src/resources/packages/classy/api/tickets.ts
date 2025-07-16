import apiFetch from '@wordpress/api-fetch';
import { applyFilters } from '@wordpress/hooks';
import { addQueryArgs } from '@wordpress/url';
import { PartialTicket, Ticket } from '../types/Ticket';
import { GetTicketApiResponse, GetTicketsApiResponse, TicketsApiParams, } from '../types/Api';
import { NonceAction, NonceTypes } from '../types/LocalizedData';
import { getLocalizedData } from '../localizedData.ts';

const apiBaseUrl = '/tribe/tickets/v1/tickets';

/**
 * Get a nonce for the specified type.
 *
 * This function retrieves the nonce for a specific type from the localized data.
 * It is used to ensure secure API requests by including the appropriate nonce.
 *
 * @since TBD
 *
 * @param {NonceTypes} type The type of nonce to retrieve.
 * @return {string} The nonce value for the specified type.
 */
const getNonce = ( type: NonceTypes ): string => {
	return getLocalizedData().nonces[ type ];
};

/**
 * Fetch tickets from the API.
 *
 * This function retrieves tickets based on the provided parameters. It  will return a promise that resolves to
 * the tickets response. If there are errors with the request, it will reject with an error message. The calling code
 * should handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {TicketsApiParams} params Optional parameters for the API request.
 * @return {Promise<GetTicketsApiResponse>} A promise that resolves to the tickets response.
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

	return new Promise<GetTicketsApiResponse>( async ( resolve, reject ) => {
		await apiFetch( { path: path } )
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
 * This function retrieves tickets associated with a specific post ID. It will return a promise that resolves to
 * an array of tickets. If there are errors with the request, it will reject with an error message. The calling code
 * should handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {number} postId The ID of the post to fetch tickets for.
 * @return {Promise<Ticket[]>} A promise that resolves to an array of tickets.
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
 * Create or update a ticket.
 *
 * This function will create a new ticket or update an existing one based on whether the ticket data
 * contains an ID greater than 0. It returns a promise that resolves to the created or updated ticket.
 * If there are errors with the request, it will reject with an error message. The calling code should
 * handle the promise appropriately to manage the response or errors.
 *
 * @since TBD
 *
 * @param {PartialTicket} ticketData The data for the ticket to create or update.
 * @return {Promise<GetTicketApiResponse>} A promise that resolves to the created or updated ticket.
 */
export const upsertTicket = async ( ticketData: PartialTicket ): Promise<GetTicketApiResponse> => {
	return new Promise<GetTicketApiResponse>( async ( resolve, reject ) => {
		const isUpdate = ticketData.id && ticketData.id > 0;
		const body: Record<string, any> = {
			ticket: {},
		};

		// Required fields
		if ( ticketData.eventId ) {
			body.post_id = ticketData.eventId.toString();
		}
		if ( ticketData.title ) {
			body.name = ticketData.title;
		}
		if ( ticketData.description ) {
			body.description = ticketData.description;
		}

		body.post_id = ticketData.eventId.toString();
		body.price = ticketData.cost || '';

		// todo: handle provider properly.
		body.provider = ticketData.provider || 'tc';

		// Date and time fields
		// todo: refine date and time handling to ensure proper format.
		if ( ticketData.availableFrom ) {
			// Extract date and time from availableFrom
			const availableFromDate = new Date( ticketData.availableFrom );
			const startDate = availableFromDate.toISOString().split( 'T' )[ 0 ];
			const startTime = availableFromDate.toTimeString().split( ' ' )[ 0 ];
			body.start_date = startDate;
			body.start_time = startTime;
		}

		if ( ticketData.availableUntil ) {
			// Extract date and time from availableUntil
			const availableUntilDate = new Date( ticketData.availableUntil );
			const endDate = availableUntilDate.toISOString().split( 'T' )[ 0 ];
			const endTime = availableUntilDate.toTimeString().split( ' ' )[ 0 ];
			body.end_date = endDate;
			body.end_time = endTime;
		}

		// Additional fields
		if ( ticketData.iac ) {
			body.iac = ticketData.iac;
		}

		// Capacity fields
		if ( ticketData.capacityDetails ) {
			const capacityType = ticketData.capacityDetails.globalStockMode;
			const capacity = ticketData.capacityDetails.max;

			// Map capacity type to ticket mode
			const isUnlimited = capacityType === 'own' || capacity === 0;

			body.ticket.mode = isUnlimited ? '' : capacityType || '';
			body.ticket.capacity = isUnlimited ? '' : capacity?.toString() || '';
		}

		// Sale price fields
		if ( ticketData.salePriceData ) {
			const salePriceData = ticketData.salePriceData;

			// Initialize sale_price object if it doesn't exist
			if ( ! body.ticket.sale_price ) {
				body.ticket.sale_price = {};
			}

			body.ticket.sale_price.checked = salePriceData.enabled ? '1' : '0';
			if ( salePriceData.salePrice ) {
				body.ticket.sale_price.price = salePriceData.salePrice;
			}
			if ( salePriceData.startDate ) {
				body.ticket.sale_price.start_date = salePriceData.startDate;
			}
			if ( salePriceData.endDate ) {
				body.ticket.sale_price.end_date = salePriceData.endDate;
			}
		}

		// Menu order
		// todo: Replace this placeholder with actual logic.
		body.menu_order = '0';

		// Set the filter as its own full string, to allow for easier discoverability when searching for it.
		const filterName = isUpdate
			? 'tec.classy.tickets.updateTicket'
			: 'tec.classy.tickets.createTicket';

		/**
		 * Filter the body of the upsert request before sending it to the API.
		 *
		 * @since TBD
		 *
		 * @param {Record<string, any>} body The object containing additional values to be sent in the request.
		 * @param {PartialTicket} ticketData The ticket data being sent.
		 */
		const additionalValues: Record<string, any> = applyFilters( filterName, {}, ticketData );

		// Append/update additional values in the body.
		Object.entries( additionalValues ).forEach( ( [ key, value ] ) => {
			if ( value !== undefined && value !== null ) {
				body[ key ] = value;
			}
		} );

		const nonceKey: NonceAction = isUpdate ? 'edit_ticket_nonce' : 'add_ticket_nonce';
		await apiFetch( {
			path: `${ apiBaseUrl }${ isUpdate ? `/${ ticketData.id }` : '' }`,
			method: isUpdate ? 'PUT' : 'POST',
			data: {
				...body,
				[nonceKey]: getNonce( isUpdate ? 'updateTicket' : 'createTicket' ),
			},
		} )
			.then( ( data ) => {
				if ( ! ( data && typeof data === 'object' ) ) {
					reject( new Error( `Failed to ${ isUpdate ? 'update' : 'create' } ticket: response did not return an object.` ) );
				} else {
					resolve( data as GetTicketApiResponse );
				}
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to ${ isUpdate ? 'update' : 'create' } ticket: ${ error.message }` ) );
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
			data: {
				remove_ticket_nonce: getNonce( 'deleteTicket' ),
			}
		} )
			.then( () => {
				resolve();
			} )
			.catch( ( error ) => {
				reject( new Error( `Failed to delete ticket: ${ error.message }` ) );
			} );
	} );
};
