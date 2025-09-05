import { GetTicketApiResponse } from '@tec/tickets/classy/types/Api';

/**
 * Mock API response for ticket operations.
 *
 * @return {GetTicketApiResponse} The mock API response.
 */
export function makeMockApiResponse(): GetTicketApiResponse {
	return {
		id: 1,
		date: '2024-01-01T12:00:00+00:00',
		date_gmt: '2024-01-01T12:00:00Z',
		guid: {
			rendered: 'https://example.com/?p=1',
		},
		link: 'https://example.com/ticket-1',
		modified: '2024-01-01T12:00:00+00:00',
		modified_gmt: '2024-01-01T12:00:00Z',
		slug: 'test-ticket',
		status: 'publish',
		permalink_template: 'https://example.com/test-ticket/',
		generated_slug: 'test-ticket',
		title: {
			rendered: 'Test Ticket',
		},
		content: {
			rendered: 'Test ticket description',
			protected: false,
		},
		excerpt: {
			rendered: 'Test ticket excerpt',
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
		description: 'Test ticket description',
		on_sale: false,
		sale_price: undefined,
		price: 25,
		regular_price: 25,
		show_description: true,
		start_date: undefined,
		end_date: undefined,
		sale_price_start_date: undefined,
		sale_price_end_date: undefined,
		event: 123,
		manage_stock: true,
		stock: 100,
		type: 'default',
		sold: 0,
		sku: undefined,
		menu_order: 0,
	};
}

/**
 * Mock expected result for ticket operations.
 *
 * @return {object} The mock expected result.
 */
export function makeMockExpectedResult() {
	return {
		id: 1,
		eventId: 123,
		name: 'Test Ticket',
		description: 'Test ticket description',
		cost: '25',
		costDetails: {
			code: 'USD',
			symbol: '$',
			position: 'prefix',
			decimalSeparator: '.',
			thousandSeparator: ',',
			precision: 2,
			value: 25,
		},
		salePriceData: {
			enabled: false,
			salePrice: '',
			startDate: '',
			endDate: '',
		},
		capacitySettings: {
			enteredCapacity: 100,
			isShared: true,
			globalStockMode: 'own',
		},
		fees: {
			availableFees: [],
			automaticFees: [],
			selectedFees: [],
		},
		provider: 'tc',
		type: 'default',
		availableFrom: '',
		availableUntil: '',
		iac: '',
		menuOrder: 0,
	};
}
