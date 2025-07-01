import { Ticket } from './Ticket';

export type StoreState = {
	allTickets: Ticket[] | null;
	tickets: Ticket[];
	currentEventId: number | null;
	isLoading: boolean;
	error: string | null;
}
