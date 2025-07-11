import { Ticket } from './Ticket';

/**
 * Parameters to be used when fetching tickets from the API.
 *
 * @since TBD
 */
export type TicketsApiParams = {
	include_post?: number[];
	per_page?: number;
	page?: number;
}

/**
 * Response structure for retrieving multiple tickets from the API.
 *
 * @since TBD
 */
export type GetTicketsApiResponse = {
	rest_url: string;
	total: number;
	total_pages: number;
	tickets: Ticket[];
}
