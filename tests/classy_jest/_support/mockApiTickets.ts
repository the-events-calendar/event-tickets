import { GetTicketApiResponse } from '@tec/tickets/classy/types/Api';

/**
 * Mock API tickets data for testing.
 *
 * @return {GetTicketApiResponse[]} The mock API tickets array.
 */
export function makeMockApiTickets(): GetTicketApiResponse[] {
	return [
		{
			id: 1,
			date: '2024-05-01T12:00:00+00:00',
			date_gmt: '2024-05-01T12:00:00Z',
			guid: {
				rendered: 'https://example.com/?p=1',
			},
			link: 'https://example.com/ticket-1',
			modified: '2024-05-15T12:00:00+00:00',
			modified_gmt: '2024-05-15T12:00:00Z',
			slug: 'sample-ticket',
			status: 'publish',
			permalink_template: 'https://example.com/sample-ticket/',
			generated_slug: 'sample-ticket',
			title: {
				rendered: 'Sample Ticket',
			},
			content: {
				rendered: 'This is a sample ticket description.',
				protected: false,
			},
			excerpt: {
				rendered: 'Sample ticket excerpt.',
				protected: false,
			},
			author: 1,
			featured_media: 0,
			comment_status: 'open',
			ping_status: 'open',
			format: 'standard',
			sticky: false,
			template: '',
			tags: [],
			description: 'This is a sample ticket description.',
			on_sale: true,
			sale_price: 20,
			price: 50,
			regular_price: 50,
			show_description: true,
			start_date: '2024-06-01 10:00:00',
			end_date: '2024-06-01 18:00:00',
			sale_price_start_date: '2024-01-01',
			sale_price_end_date: '2024-12-31',
			event: 123,
			manage_stock: true,
			stock: 100,
			type: 'default',
			sold: 42,
			sku: 'TICKET-123',
			menu_order: 0,
		},
	];
}
