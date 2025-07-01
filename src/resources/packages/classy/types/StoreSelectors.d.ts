import { Ticket } from './Ticket';

export type StoreSelectors = {
	// New selectors for tickets management
	getTicketsByEventId: (state: any, eventId: number) => Ticket[];
	getTickets: (state: any) => Ticket[];
	getTicketsForPost: (state: any) => Ticket[];
	getCurrentPostId: (state: any) => number | null;
	getIsLoading: (state: any) => boolean;
	getError: (state: any) => string | null;
	getTicketById: (state: any) => (ticketId: number) => Ticket | undefined;
}
