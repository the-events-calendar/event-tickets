import { StoreState, StoreSelectors } from '../types/Store';
import { Ticket } from '../types/Ticket';


const getTickets = ( state: StoreState ) => {
	return state?.tickets || [];
}

const getTicketsByEventId = ( state: StoreState, eventId: number ): Ticket[] => {
	console.log( 'getTicketsByEventId called with eventId:', eventId );
	console.log( 'Current state:', state );
	return [];
};


export const selectors = {
	getTicketsByEventId,
	getTickets,
} as StoreSelectors;
