import { Ticket } from '../types/Ticket';

export const SET_TICKETS = 'SET_TICKETS';
export const SET_TICKETS_FOR_EVENT = 'SET_TICKETS_FOR_EVENT';

type SetTicketsForEventAction = {
	type: typeof SET_TICKETS_FOR_EVENT;
	postId: number;
	tickets: Ticket[];
}

type SetTicketsAction = {
	type: typeof SET_TICKETS;
	tickets: Ticket[];
}

// export function setTicketsForPost(
// 	postId: number,
// 	tickets: Ticket[]
// ): SetTicketsForPostAction {
// 	return {
// 		type: SET_TICKETS_FOR_POST,
// 		payload: {
// 			postId: postId,
// 			tickets: tickets,
// 		}
// 	};
// }

export default {
	setTicketsForPost: ( postId: number, tickets: Ticket[] ): SetTicketsForEventAction => ( { type: SET_TICKETS_FOR_EVENT, postId, tickets } ),
	setTickets: ( tickets: Ticket[] ): SetTicketsAction => ( { type: SET_TICKETS, tickets } ),
};
