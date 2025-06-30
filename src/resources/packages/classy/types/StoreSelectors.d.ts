import { Ticket } from './Ticket';

export type StoreSelectors = {
	// Legacy selectors for backward compatibility
	getTicketPrice: (state: any) => number;
	getTicketStock: (state: any) => number;
	getTicketStartDate: (state: any) => string;
	getTicketEndDate: (state: any) => string;
	getTicketIsFree: (state: any) => boolean;
	getTicketQuantity: (state: any) => number;
	
	// New selectors for tickets management
	getTickets: (state: any) => Ticket[];
	getTicketsForPost: (state: any) => Ticket[];
	getCurrentPostId: (state: any) => number | null;
	getIsLoading: (state: any) => boolean;
	getError: (state: any) => string | null;
	getTicketById: (state: any) => (ticketId: number) => Ticket | undefined;
}
