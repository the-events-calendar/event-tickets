import { Ticket } from './Ticket';

export type StoreState = {
	tickets: Ticket[] | null;
	isLoading: boolean;
	error: string | null;
}

export type StoreSelectors = {
	getTickets: (state: any) => Ticket[];
	getIsLoading: (state: any) => boolean;
	getError: (state: any) => string | null;
	getTicketById: (state: any) => (ticketId: number) => Ticket | undefined;
}
