import { fetchTicketsForPost } from '../api';
import { TicketSettings } from '../types/Ticket';
import { StoreDispatch } from '../types/Store';

export default {
	getTickets: ( eventId: number ) =>
		async ( { dispatch }: { dispatch: StoreDispatch } ): Promise<void> => {
			if ( ! eventId ) {
				console.warn( 'Event ID is required to fetch tickets.' );
				return;
			}

			await fetchTicketsForPost( eventId )
				.then( ( response: TicketSettings[] ) => {
					dispatch.setTickets( response );
				} )
				.catch( ( error ) => {
					// Log an error in the console.
					console.error( `Error getting tickets for event ${ eventId }: ${ error }` );
					dispatch.setTickets( [] );
				} )
				.finally( () => {
					dispatch.setIsLoading( false );
				} );
		},
};
