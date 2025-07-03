import { Ticket } from '../types/Ticket';
import {
	SET_TICKETS,
	SetTicketsAction,
} from '../types/Actions';

const setTickets = ( tickets: Ticket[] ): SetTicketsAction => ( {
	type: SET_TICKETS,
	tickets
} );

export default {
	setTickets,
};
