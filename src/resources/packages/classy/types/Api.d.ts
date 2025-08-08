import { CurrencyPosition } from '@tec/common/classy/types/CurrencyPosition';
import { FeesData, TicketId, TicketType } from './Ticket';

/**
 * Parameters to be used when fetching tickets from the API.
 *
 * @since TBD
 */
export type GetTicketsApiParams = {
	include_post?: number[];
	per_page?: number;
	page?: number;
};

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
};

type ApiDate = {
	year: string;
	month: string;
	day: string;
	hour: string;
	minutes: string;
	seconds: string;
}

/**
 * Response structure for retrieving a single ticket from the API.
 *
 * This structure may be extended in the future to include more detailed information.
 *
 * @since TBD
 */
export type GetTicketApiResponse = {
	id: TicketId;
	title: string;
	description: string;
	rest_url: string;
	post_id: number;
	sale_price_data: {
		enabled: string;
		sale_price: string;
		start_date: string;
		end_date: string;
	};
	provider: string;
	type: TicketType;
	iac: string;
	capacity: number | '';
	capacity_details: {
		max: number;
		global_stock_mode: string;
	}

	// Price/cost details.
	cost: string;
	cost_details: {
		currency_symbol: string;
		currency_position: CurrencyPosition;
		currency_decimal_separator: string;
		currency_decimal_numbers: number;
		currency_thousand_separator: string;
		suffix?: string | null;

		// There should be only one value in this array, but it is an array to match the normal Event price structure.
		values: string[];
	};
	price?: string | number;
	fees: FeesData;

	// For sale dates.
	available_from: string;
	available_until: string;
	available_from_details: ApiDate;
	available_until_details: ApiDate;


	// Detail objects.


	// Additional fields from the WordPress post object.
	author: string | number;
	date: string;
	date_utc: string;
	modified: string;
	modified_utc: string;
	status: string;
};

/**
 * Request structure for creating or updating a ticket.
 *
 * @since TBD
 */
export type UpsertTicketApiRequest = {
	post_id: string;
	name: string;
	description: string;
	price: string;
	provider: string;
	type?: string;
	start_date?: string;
	start_time?: string;
	end_date?: string;
	end_time?: string;
	iac?: string;
	ticket?: {
		mode?: string;
		capacity?: string;
		sale_price?: {
			checked?: string;
			price?: string;
			start_date?: string;
			end_date?: string;
		};
	};
	menu_order: string;

	// Additional values from filters.
	[key: string]: any;
};

/**
 * Request structure for creating a ticket.
 *
 * @since TBD
 */
export type CreateTicketApiRequest = UpsertTicketApiRequest & {
	add_ticket_nonce: string;
};

/**
 * Request structure for updating a ticket.
 *
 * @since TBD
 */
export type UpdateTicketApiRequest = UpsertTicketApiRequest & {
	id: number;
	edit_ticket_nonce: string;
};

/**
 * Request structure for deleting a ticket.
 *
 * @since TBD
 */
export type DeleteTicketApiRequest = {
	remove_ticket_nonce: string;
};
