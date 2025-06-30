import { fetchTicketsForPost, mapApiTicketToTicket } from '../api';
import { Ticket } from '../types/Ticket';

export default {};
// export default {
// 	getTicketsForPost:
// 		( postId: number ) =>
// 			async ( { dispatch } ): Promise<void> => {
// 				if ( ! postId ) {
// 					return;
// 				}
//
// 				fetchTicketsForPost( postId )
// 					.then( ( tickets: Ticket[] ) => {
// 						dispatch.setTicketsForPost( postId, tickets.map( mapApiTicketToTicket ) );
// 					} )
// 					.catch( ( error ) => {
// 						// Log an error in the console.
// 						console.error( `Error getting tickets for post ${ postId }: ${ error }` );
// 						dispatch.setTicketsForPost( postId, [] );
// 					} );
// 			}
// };
