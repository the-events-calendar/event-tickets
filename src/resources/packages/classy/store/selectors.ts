import { STORE_NAME } from './constants';
import { StoreState } from '../types/StoreState';
import { StoreSelectors } from '../types/StoreSelectors';
import { Ticket } from '../types/Ticket';

// Base selectors
const getState = ( state: any ) => state[ STORE_NAME ] as StoreState;

const getTickets = ( state: any ) => getState( state ).tickets;

const getCurrentPostId = ( state: any ) => getState( state ).currentPostId;

const getIsLoading = ( state: any ) => getState( state ).isLoading;

const getError = ( state: any ) => getState( state ).error;

const getTicketsByEventId = ( eventId: number ): Ticket[] => {
	return [];
}

// Derived selectors
const getTicketsForPost = ( state: any ) => {
	const tickets = getTickets( state );
	const currentPostId = getCurrentPostId( state );
	if ( ! currentPostId ) return [];
	return tickets.filter( ( ticket: Ticket ) => ticket.postId === currentPostId );
};

const getTicketById = ( state: any ) => ( ticketId: number ) => {
	const tickets = getTickets( state );
	return tickets.find( ( ticket: Ticket ) => ticket.id === ticketId );
};

// Legacy selectors for backward compatibility
const getTicketPrice = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	if ( tickets.length === 0 ) return 0;
	const firstTicket = tickets[ 0 ];
	return parseFloat( firstTicket.cost.replace( /[^0-9.]/g, '' ) ) || 0;
};

const getTicketStock = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	if ( tickets.length === 0 ) return 0;
	const firstTicket = tickets[ 0 ];
	return firstTicket.capacityDetails.available;
};

const getTicketStartDate = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	if ( tickets.length === 0 ) return '';
	const firstTicket = tickets[ 0 ];
	return firstTicket.availableFrom;
};

const getTicketEndDate = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	if ( tickets.length === 0 ) return '';
	const firstTicket = tickets[ 0 ];
	return firstTicket.availableUntil;
};

const getTicketIsFree = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	if ( tickets.length === 0 ) return false;
	const firstTicket = tickets[ 0 ];
	return firstTicket.cost === '$0.00' || firstTicket.cost === '0';
};

const getTicketQuantity = ( state: any ) => {
	const tickets = getTicketsForPost( state );
	return tickets.length;
};

export const selectors = {
	// New selectors for tickets management
	getTickets,
	getTicketsForPost,
	getCurrentPostId,
	getIsLoading,
	getError,
	getTicketById,

	// Legacy selectors for backward compatibility
	getTicketPrice,
	getTicketStock,
	getTicketStartDate,
	getTicketEndDate,
	getTicketIsFree,
	getTicketQuantity,
} as StoreSelectors;
