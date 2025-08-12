import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, jest, test } from '@jest/globals';
import { GetTicketApiResponse, GetTicketsApiResponse } from '../../../src/resources/packages/classy/types/Api';
import { fetchTickets, fetchTicketsForPost, upsertTicket } from '../../../src/resources/packages/classy/api';
import apiFetch from '@wordpress/api-fetch';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

describe( 'Ticket API', () => {
	const resetModules = () => {
		jest.resetModules();
	};

	const resetMocks = () => {
		jest.resetAllMocks();
	};

	beforeAll( resetModules );
	afterAll( resetModules );
	beforeEach( resetMocks );
	afterEach( resetMocks );

	const restEndpoint = '/tec/classy/v1/tickets';
	const restUrl = `https://example.com/wp-json${restEndpoint}`;

	describe( 'fetchTickets', () => {
		const mockApiTickets: GetTicketApiResponse[] = [
			{
				id: 1,
				title: 'Sample Ticket',
				description: 'This is a sample ticket description.',
				rest_url: `${restUrl}/1`,
				post_id: 123,
				sale_price_data: {
					enabled: '1',
					sale_price: '20.00',
					start_date: '2024-01-01 00:00:00',
					end_date: '2024-12-31 23:59:59',
				},
				provider: 'tc',
				type: 'default',
				iac: 'ABC123',
				capacity: 100,
				capacity_details: {
					max: 100,
					global_stock_mode: 'own',
				},

				// Price/cost details.
				cost: '50.00',
				cost_details: {
					currency_symbol: '$',
					currency_position: 'prefix',
					currency_decimal_separator: '.',
					currency_decimal_numbers: 2,
					currency_thousand_separator: ',',
					suffix: '',
					values: [ '50.00' ],
				},

				fees: {
					availableFees: [],
					automaticFees: [],
					selectedFees: [],
				},

				// For sale dates.
				available_from: '2024-06-01 10:00:00',
				available_until: '2024-06-01 18:00:00',
				available_from_details: {
					year: '2024',
					month: '06',
					day: '01',
					hour: '10',
					minutes: '00',
					seconds: '00',
				},
				available_until_details: {
					year: '2024',
					month: '06',
					day: '01',
					hour: '18',
					minutes: '00',
					seconds: '00',
				},

				// Additional fields from the WordPress post object.
				author: 1,
				date: '2024-05-01 12:00:00',
				date_utc: '2024-05-01 12:00:00',
				modified: '2024-05-15 12:00:00',
				modified_utc: '2024-05-15 12:00:00',
				status: 'publish',
			}
		];

		test( 'calls api-fetch with correct parameters', async () => {
			const mockTicketsData: GetTicketsApiResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
				tickets: mockApiTickets,
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockTicketsData );

			const result = await fetchTickets();

			expect( result ).toEqual( mockTicketsData );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
			} );
		} );
	} );
} );
