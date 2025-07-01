import { STORE_NAME } from './constants';
import { StoreState } from '../types/StoreState';
import { StoreSelectors } from '../types/StoreSelectors';
import { Ticket } from '../types/Ticket';

// Base selectors


const getTickets = ( state: StoreState ) => {
	return state?.tickets || [];
}

const getTicketsByEventId = ( state: StoreState, eventId: number ): Ticket[] => {
	console.log( 'getTicketsByEventId called with eventId:', eventId );
	console.log( 'Current state:', state );
	return [];
};

// Derived selectors




export const selectors = {
	// New selectors for tickets management
	getTicketsByEventId,
	getTickets,
} as StoreSelectors;
