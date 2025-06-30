import { Ticket } from '../types/Ticket';
import { TicketsApiResponse } from '../types/TicketsApiResponse';
import { TicketsApiParams } from '../types/TicketsApiParams';

/**
 * Fetch tickets from the API
 */
export const fetchTickets = async ( params: TicketsApiParams = {} ): Promise<TicketsApiResponse> => {
	const searchParams = new URLSearchParams();

	if ( params.include_post ) {
		params.include_post.forEach( postId => {
			searchParams.append( 'include_post', postId.toString() );
		} );
	}

	if ( params.per_page ) {
		searchParams.set( 'per_page', params.per_page.toString() );
	}

	if ( params.page ) {
		searchParams.set( 'page', params.page.toString() );
	}

	const url = `/tribe/tickets/v1/tickets/${ searchParams.toString() ? `?${ searchParams.toString() }` : '' }`;

	const response = await fetch( url, {
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
		},
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to fetch tickets: ${ response.statusText }` );
	}

	return response.json();
};

/**
 * Fetch tickets for a specific post ID
 */
export const fetchTicketsForPost = async ( postId: number ): Promise<Ticket[]> => {
	const response = await fetchTickets( { include_post: [ postId ] } );
	return response.tickets;
};

/**
 * Create a new ticket
 */
export const createTicket = async ( ticketData: Partial<Ticket> ): Promise<Ticket> => {
	const response = await fetch( '/tribe/tickets/v1/tickets/', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( ticketData ),
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to create ticket: ${ response.statusText }` );
	}

	return response.json();
};

/**
 * Update an existing ticket
 */
export const updateTicket = async ( ticketId: number, ticketData: Partial<Ticket> ): Promise<Ticket> => {
	const response = await fetch( `/tribe/tickets/v1/tickets/${ ticketId }`, {
		method: 'PUT',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( ticketData ),
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to update ticket: ${ response.statusText }` );
	}

	return response.json();
};

/**
 * Delete a ticket
 */
export const deleteTicket = async ( ticketId: number ): Promise<void> => {
	const response = await fetch( `/tribe/tickets/v1/tickets/${ ticketId }`, {
		method: 'DELETE',
		headers: {
			'Content-Type': 'application/json',
		},
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to delete ticket: ${ response.statusText }` );
	}
};

export const mapApiTicketToTicket = ( apiTicket: any ): Partial<Ticket> => {
	return {
		id: Number( apiTicket.id ),
		eventId: Number( apiTicket.post_id ),
		title: apiTicket.title,
		description: apiTicket.description,
		provider: apiTicket.provider,
		type: apiTicket.type,
		status: apiTicket.status,
	}
}
