import { fetchTicketsForPost } from '../api';
import { Ticket } from '../types/Ticket';

export default {
	getTickets: ( eventId: number ) =>
		async ( { dispatch } ): Promise<void> => {
			if ( ! eventId ) {
				console.warn( 'Event ID is required to fetch tickets.' );
				return;
			}

			console.log( `Fetching tickets for event ID: ${ eventId }` );

			await fetchTicketsForPost( eventId )
				.then( ( tickets: Ticket[] ) => {
					dispatch.setTickets( tickets );
				} )
				.catch( ( error ) => {
					// Log an error in the console.
					console.error( `Error getting tickets for event ${ eventId }: ${ error }` );
					dispatch.setTickets( [] );
				} );
		},
};
