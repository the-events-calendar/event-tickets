import { Ticket } from '../types/Ticket';
import {
	SET_TICKETS,
	SET_TICKETS_FOR_EVENT,
	SetTicketsAction,
	SetTicketsForEventAction,
} from '../types/Actions';

export default {
	setTicketsForPost: ( postId: number, tickets: Ticket[] ): SetTicketsForEventAction => ( {
		type: SET_TICKETS_FOR_EVENT,
		postId,
		tickets
	} ),
	setTickets: ( tickets: Ticket[] ): SetTicketsAction => ( {
		type: SET_TICKETS,
		tickets
	} ),
};
