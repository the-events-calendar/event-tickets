/**
 * Parameters to be used when fetching tickets from the API.
 *
 * These parameters correspond to the query parameters supported by the GET /tickets endpoint.
 *
 * @since TBD
 */
export type GetTicketsApiParams = {
	/** The collection page number. Default: 1, Minimum: 1 */
	page?: number;

	/** Maximum number of items to be returned in result set. Default: 10, Maximum: 100, Minimum: 1 */
	per_page?: number;

	/** Limit results to those matching a string. */
	search?: string;

	/** Limit result set to tickets assigned to specific events. */
	event?: number;

	/** Sort collection by attribute. Default: "date" */
	orderby?: 'date' | 'id' | 'include' | 'relevance' | 'slug' | 'include_slugs' | 'title';

	/** Order sort attribute ascending or descending. Default: "desc" */
	order?: 'asc' | 'desc';

	/** Limit result set to tickets assigned one or more statuses. Default: "publish" */
	status?: string;

	/** Limit result set to specific IDs. */
	include?: number[];

	/** Ensure result set excludes specific IDs. */
	exclude?: number[];

	/** Include tickets marked as hidden from view. */
	show_hidden?: boolean;
};

/**
 * Response structure for retrieving multiple tickets from the API.
 *
 * The /tickets endpoint returns an array of Ticket objects directly.
 * Pagination information is available in the response headers:
 * - X-WP-Total: Total number of tickets matching the request
 * - X-WP-TotalPages: Total number of pages for the request
 * - Link: RFC 5988 Link header for pagination
 *
 * @since TBD
 */
export type GetTicketsApiResponse = GetTicketApiResponse[];

/**
 * Base TEC Post Entity structure as returned by the REST API.
 *
 * @since TBD
 */
type TECPostEntity = {
	date?: string;
	date_gmt?: string;
	guid?: {
		rendered: string;
	};
	id: number;
	link: string;
	modified: string;
	modified_gmt: string;
	slug: string;
	status: string;
	permalink_template: string;
	generated_slug: string;
	title: {
		rendered: string;
	};
	content: {
		rendered: string;
		protected: boolean;
	};
	excerpt: {
		rendered: string;
		protected: boolean;
	};
	author: number;
	featured_media: number;
	comment_status: string;
	ping_status: string;
	format: string;
	sticky: boolean;
	template: string;
	tags: number[];
};

/**
 * Response structure for retrieving a single ticket from the API.
 *
 * This structure matches the actual API response from the /tickets/{id} endpoint.
 *
 * @since TBD
 */
export type GetTicketApiResponse = TECPostEntity & {
	capacity?: number;
	description: string;
	price: number;
	regular_price: number;
	show_description: boolean;
	start_date?: string;
	end_date?: string;
	on_sale?: boolean;
	sale_price?: number;
	sale_price_start_date?: string;
	sale_price_end_date?: string;
	sale_price_enabled?: boolean;
	event: number;
	manage_stock: boolean;
	stock?: number;
	type: string;
	sold: number;
	sku?: string;
	menu_order: number;
};

/**
 * Base TEC Post Entity Request Body structure as expected by the REST API.
 *
 * @since TBD
 */
type TECPostEntityRequestBody = {
	date?: string;
	date_gmt?: string;
	slug?: string;
	status?: 'publish' | 'pending' | 'draft' | 'future' | 'private';
	title?: string;
	content?: string;
	excerpt?: string;
	author?: number;
	featured_media?: number;
	comment_status?: 'open' | 'closed';
	ping_status?: 'open' | 'closed';
	format?: 'standard' | 'aside' | 'chat' | 'gallery' | 'link' | 'image' | 'quote' | 'status' | 'video' | 'audio';
	sticky?: boolean;
	template?: string;
	tags?: number[];
};

/**
 * Request structure for creating or updating a ticket.
 *
 * This structure matches the actual API request body for the POST /tickets and PUT /tickets/{id} endpoints.
 *
 * @since TBD
 */
export type UpsertTicketApiRequest = TECPostEntityRequestBody & {
	/** The ID of the post this ticket is associated with. Normally an event-like post. */
	event?: number;

	/** The price of the ticket */
	price?: number;

	/** The sale price of the ticket */
	sale_price?: number;

	/** The start date for the sale price */
	sale_price_start_date?: string;

	/** The end date for the sale price */
	sale_price_end_date?: string;

	/** Whether the ticket has a sale price enabled. */
	sale_price_enabled?: boolean;

	/** The capacity of the ticket */
	capacity?: number | '';

	/** The capacity of the event */
	event_capacity?: number;

	/** The stock quantity available */
	stock?: number;

	/** Whether to show the ticket description */
	show_description?: boolean;

	/** The stock mode of the ticket */
	stock_mode?: 'own' | 'capped' | 'global' | 'unlimited';

	/** The type of ticket */
	type?: string;

	/** The start sale date of the ticket */
	start_date?: string;

	/** The end sale date of the ticket */
	end_date?: string;

	/** The SKU of the ticket */
	sku?: string;

	/** The menu order of the ticket */
	menu_order?: number;
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
