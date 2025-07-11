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
	tickets: GetTicketApiResponse[];
}

/**
 * Response structure for retrieving a single ticket from the API.
 *
 * @since TBD
 */
export type GetTicketApiResponse = {
	author: number;
	status: string;
	date: string;
	date_utc: string;
	modified: string;
	modified_utc: string;
	title: string;
	rest_url: string;
} & Ticket;
