import { fetchTickets, fetchTicketsForPost, mapApiTicketToTicket } from '../api';
import { Ticket } from '../types/Ticket';
import { TicketsApiResponse } from '../types/TicketsApiResponse';

export default {
	getTicketsForPost:
		( postId: number ) =>
			async ( { dispatch } ): Promise<void> => {
				if ( ! postId ) {
					return;
				}

				fetchTicketsForPost( postId )
					.then( ( tickets: Ticket[] ) => {
						dispatch.setTicketsForPost( postId, tickets.map( mapApiTicketToTicket ) );
					} )
					.catch( ( error ) => {
						// Log an error in the console.
						console.error( `Error getting tickets for post ${ postId }: ${ error }` );
						dispatch.setTicketsForPost( postId, [] );
					} );
			},

	getTickets:
		() =>
		async ( { dispatch } ): Promise<void> => {
		await fetchTickets()
			.then( ( response: TicketsApiResponse ) => {
				console.log( 'Fetched tickets:', response );
				const fetchedTickets = response.tickets || [];
				const mappedTickets = fetchedTickets.map( mapApiTicketToTicket );
				dispatch.setTickets( mappedTickets );
			} )
			.catch( ( error ) => {
				// Log an error in the console.
				console.error( `Error getting tickets: ${ error }` );
				dispatch.setTickets( [] );
			} );
	}
};
