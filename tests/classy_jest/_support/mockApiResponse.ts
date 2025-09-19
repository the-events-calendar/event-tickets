import { GetTicketApiResponse } from '@tec/tickets/classy/types/Api';

const defaultEventId = 123;

const getBaseTicket = (): Partial< GetTicketApiResponse > => {
	return {
		date: '2024-01-01T12:00:00+00:00',
		date_gmt: '2024-01-01T12:00:00Z',
		link: 'https://example.com/tickets/test-ticket/',
		modified: '2024-01-01T12:00:00+00:00',
		modified_gmt: '2024-01-01T12:00:00Z',
		status: 'publish',
		author: 1,
		featured_media: 0,
		comment_status: 'open',
		ping_status: 'open',
		format: 'standard',
		sticky: false,
		template: '',
		tags: [],
		on_sale: false,
		sale_price: undefined,
		price: 25,
		regular_price: 25,
		show_description: true,
		start_date: undefined,
		end_date: undefined,
		sale_price_start_date: undefined,
		sale_price_end_date: undefined,
		manage_stock: true,
		stock: 100,
		type: 'default',
		sold: 0,
		sku: undefined,
		menu_order: 0,
	};
};

/**
 * Generate a mock ticket with a specific ID.
 *
 * @param {number} id The ticket ID.
 * @param eventId
 * @return {GetTicketApiResponse} The generated mock ticket.
 */
const generateApiTicket = ( id: number, eventId: number ): GetTicketApiResponse => {
	const slug = `test-ticket-${ id }`;
	const baseTicket = getBaseTicket();

	return {
		...baseTicket,
		id,
		slug: slug,
		generated_slug: slug,
		title: {
			rendered: `Sample Ticket ${ id }`,
		},
		content: {
			rendered: `This is a sample ticket description for ticket ${ id }.`,
			protected: false,
		},
		excerpt: {
			rendered: `Sample ticket excerpt for ticket ${ id }.`,
			protected: false,
		},
		description: `This is a sample ticket description for ticket ${ id }.`,
		link: `https://example.com/tickets/${ slug }`,
		sold: id * 10,
		event: eventId,
	} as GetTicketApiResponse;
};

/**
 * Mock API tickets data for testing.
 *
 * @return {GetTicketApiResponse[]} The mock API tickets array.
 */
export function makeMockApiTickets(
	numberOfTickets: number,
	eventId: number = defaultEventId
): GetTicketApiResponse[] {
	const tickets: GetTicketApiResponse[] = [];
	for ( let i = 1; i <= numberOfTickets; i++ ) {
		tickets.push( generateApiTicket( i, eventId ) );
	}

	return tickets;
}

/**
 * Mock API response for ticket operations.
 *
 * @return {GetTicketApiResponse} The mock API response.
 */
export function makeMockApiResponse(): GetTicketApiResponse {
	return generateApiTicket( 1, defaultEventId );
}
