import { Ticket } from '../types/Ticket';
import {
	SET_TICKETS,
	SetTicketsAction,
} from '../types/Actions';

export default {
	setTickets: ( tickets: Ticket[] ): SetTicketsAction => ( {
		type: SET_TICKETS,
		tickets
	} ),
};
