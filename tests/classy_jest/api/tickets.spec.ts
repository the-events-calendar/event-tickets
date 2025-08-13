import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, jest, test } from '@jest/globals';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { GetTicketApiResponse, GetTicketsApiResponse } from '../../../src/resources/packages/classy/types/Api';
import { fetchTickets, fetchTicketsForPost, upsertTicket } from '../../../src/resources/packages/classy/api';
import { TicketSettings } from '../../../src/resources/packages/classy/types/Ticket';

/**
 * Helper function to create the expected API path with query parameters.
 * This makes the test more readable and maintainable.
 *
 * @param {string} basePath - The base API endpoint path.
 * @param {Record<string, any>} queryArgs - Query arguments to append.
 * @return {string} The complete path with query parameters.
 */
const createExpectedPath = ( basePath: string, queryArgs: Record<string, any> = {} ): string => {
	return addQueryArgs( basePath, queryArgs );
};

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
	const restUrl = `https://example.com/wp-json${ restEndpoint }`;

	describe( 'fetchTickets', () => {
		const mockApiTickets: GetTicketApiResponse[] = [
			{
				id: 1,
				title: 'Sample Ticket',
				description: 'This is a sample ticket description.',
				rest_url: `${ restUrl }/1`,
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
		const mockApiMappedTickets: TicketSettings[] = [
			{
				id: 1,
				eventId: 123,
				name: 'Sample Ticket',
				description: 'This is a sample ticket description.',
				cost: '50.00',
				costDetails: {
					currencySymbol: '$',
					currencyPosition: 'prefix',
					currencyDecimalSeparator: '.',
					currencyThousandSeparator: ',',
					suffix: '',
					values: [ 50 ],
				},
				salePriceData: {
					enabled: true,
					salePrice: '20.00',
					startDate: new Date( '2024-01-01 00:00:00' ).toISOString(),
					endDate: new Date( '2024-12-31 23:59:59' ).toISOString(),
				},
				capacitySettings: {
					enteredCapacity: 100,
					isShared: false,
				},
				fees: {
					availableFees: [],
					automaticFees: [],
					selectedFees: [],
				},
				provider: 'tc',
				type: 'default',
			},
		];

		test( 'calls fetchTickets with correct parameters', async () => {
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

		test( 'calls fetchTicketsForPost with correct parameters', async () => {
			const mockTicketsData: GetTicketsApiResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
				tickets: mockApiTickets,
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockTicketsData );

			const result = await fetchTicketsForPost( 123 );

			expect( result ).toEqual( mockApiMappedTickets );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: createExpectedPath( restEndpoint, { include_post: [ 123 ] } ),
			} );
		} );

		test( 'rejects when apiFetch throws an error', async () => {
			const apiError = new Error( 'Network error' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( fetchTickets() ).rejects.toThrow( 'Failed to fetch tickets: Network error' );
		} );

		test( 'rejects when response is not an object', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( 'not an object' );

			await expect( fetchTickets() ).rejects.toThrow( 'Failed to fetch tickets: response did not return an object.' );
		} );

		test( 'rejects when response is null', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( null );

			await expect( fetchTickets() ).rejects.toThrow( 'Failed to fetch tickets: response did not return an object.' );
		} );

		test( 'rejects when response is undefined', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( undefined );

			await expect( fetchTickets() ).rejects.toThrow( 'Failed to fetch tickets: response did not return an object.' );
		} );

		test( 'rejects when response object is missing tickets property', async () => {
			const invalidResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
				// missing tickets property
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( invalidResponse );

			await expect( fetchTickets() ).rejects.toThrow( 'Tickets fetch request did not return an object with tickets and total properties.' );
		} );

		test( 'rejects when response object is missing total property', async () => {
			const invalidResponse = {
				rest_url: restUrl,
				tickets: mockApiTickets,
				total_pages: 1,
				// missing total property
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( invalidResponse );

			await expect( fetchTickets() ).rejects.toThrow( 'Tickets fetch request did not return an object with tickets and total properties.' );
		} );

		test( 'rejects when response object is missing both tickets and total properties', async () => {
			const invalidResponse = {
				rest_url: restUrl,
				total_pages: 1,
				// missing both tickets and total properties
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( invalidResponse );

			await expect( fetchTickets() ).rejects.toThrow( 'Tickets fetch request did not return an object with tickets and total properties.' );
		} );

		test( 'handles query parameters correctly', async () => {
			const mockTicketsData: GetTicketsApiResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
				tickets: mockApiTickets,
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockTicketsData );

			const params = {
				include_post: [ 123, 456 ],
				per_page: 10,
				page: 2,
			};

			const result = await fetchTickets( params );

			expect( result ).toEqual( mockTicketsData );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: createExpectedPath( restEndpoint, params ),
			} );
		} );

		test( 'handles empty query parameters', async () => {
			const mockTicketsData: GetTicketsApiResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
				tickets: mockApiTickets,
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockTicketsData );

			const result = await fetchTickets( {} );

			expect( result ).toEqual( mockTicketsData );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
			} );
		} );
	} );
} );
