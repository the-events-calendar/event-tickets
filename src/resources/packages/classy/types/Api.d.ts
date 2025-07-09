import { Ticket } from "./Ticket";

export type TicketsApiParams = {
	include_post?: number[];
	per_page?: number;
	page?: number;
}

export type TicketsApiResponse = {
	rest_url: string;
	total: number;
	total_pages: number;
	tickets: Ticket[];
}
