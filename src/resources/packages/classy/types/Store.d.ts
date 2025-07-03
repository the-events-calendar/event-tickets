import { Ticket } from './Ticket';

export type StoreState = {
	allTickets: Ticket[] | null; // Todo: remove this when not needed.
	tickets: Ticket[] | null;
	isLoading: boolean;
	error: string | null;
}

export type StoreSelectors = {
	// New selectors for tickets management
	getTicketsByEventId: (state: any, eventId: number) => Ticket[];
	getTickets: (state: any) => Ticket[];
	getIsLoading: (state: any) => boolean;
	getError: (state: any) => string | null;
	getTicketById: (state: any) => (ticketId: number) => Ticket | undefined;
}
