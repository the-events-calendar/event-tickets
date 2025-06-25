import { Ticket } from './Ticket';

export type TicketsApiResponse = {
	rest_url: string;
	total: number;
	total_pages: number;
	tickets: Ticket[];
}
