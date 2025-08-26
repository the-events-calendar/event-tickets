import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, jest, test } from '@jest/globals';
import apiFetch from '@wordpress/api-fetch';
import { fetchTickets, fetchTicketsForPost, upsertTicket } from '@tec/tickets/classy/api';
import { makeMockApiTickets } from '../_support/mockApiTickets';
import { makeMockMappedTickets } from '../_support/mockMappedTickets';
import {
	makeMockTicketDataForCreate,
	makeMockTicketDataForUpdate,
	makeMockTicketDataWithSalePrice,
	makeMockTicketDataWithDates,
	makeMockTicketDataWithIAC,
	makeMockTicketDataWithMenuOrder,
} from '../_support/mockTicketData';
import { makeMockApiResponse, makeMockExpectedResult } from '../_support/mockApiResponse';
import { createExpectedPath, TEST_CONSTANTS } from '../_support/testHelpers';

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

	const { tecExperimentalHeader, restEndpoint, restUrl } = TEST_CONSTANTS;

	describe( 'fetchTickets', () => {
		const mockApiTickets = makeMockApiTickets();
		const mockApiMappedTickets = makeMockMappedTickets();

		test( 'calls fetchTickets with correct parameters', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiTickets );

			const result = await fetchTickets();

			expect( result ).toEqual( mockApiTickets );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
			} );
		} );

		test( 'calls fetchTicketsForPost with correct parameters', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiTickets );

			const result = await fetchTicketsForPost( 123 );

			expect( result ).toEqual( mockApiMappedTickets );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: createExpectedPath( restEndpoint, { event: 123 } ),
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
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

			await expect( fetchTickets() ).rejects.toThrow(
				'Failed to fetch tickets: response did not return an object.'
			);
		} );

		test( 'rejects when response is null', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( null );

			await expect( fetchTickets() ).rejects.toThrow(
				'Failed to fetch tickets: response did not return an object.'
			);
		} );

		test( 'rejects when response is undefined', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( undefined );

			await expect( fetchTickets() ).rejects.toThrow(
				'Failed to fetch tickets: response did not return an object.'
			);
		} );

		test( 'accepts response that is an object but not an array', async () => {
			const invalidResponse = {
				rest_url: restUrl,
				total: 1,
				total_pages: 1,
			};

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( invalidResponse );

			const result = await fetchTickets();
			expect( result ).toEqual( invalidResponse );
		} );

		test( 'handles query parameters correctly', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiTickets );

			const params = {
				event: 123,
				per_page: 10,
				page: 2,
			};

			const result = await fetchTickets( params );

			expect( result ).toEqual( mockApiTickets );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: createExpectedPath( restEndpoint, params ),
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
			} );
		} );

		test( 'handles empty query parameters', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiTickets );

			const result = await fetchTickets( {} );

			expect( result ).toEqual( mockApiTickets );
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
			} );
		} );
	} );

	describe( 'upsertTicket', () => {
		const mockTicketData = makeMockTicketDataForCreate();
		const mockUpdatedTicketData = makeMockTicketDataForUpdate();
		const mockApiResponse = makeMockApiResponse();

		test( 'creates a new ticket successfully', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			const result = await upsertTicket( mockTicketData );

			expect( result ).toEqual( makeMockExpectedResult() );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					title: 'Test Ticket',
					content: 'Test ticket description',
					event: 123,
					price: 25,
					type: 'default',
					show_description: true,
					capacity: 100,
					stock: 100,
					stock_mode: 'own',
					add_ticket_nonce: expect.any( String ),
				} ),
			} );
		} );

		test( 'updates an existing ticket successfully', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			const result = await upsertTicket( mockUpdatedTicketData );

			expect( result ).toEqual( makeMockExpectedResult() );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: `${ restEndpoint }/1`,
				method: 'PUT',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					title: 'Test Ticket',
					content: 'Test ticket description',
					event: 123,
					price: 25,
					type: 'default',
					show_description: true,
					capacity: 100,
					stock: 100,
					stock_mode: 'own',
					edit_ticket_nonce: expect.any( String ),
				} ),
			} );
		} );

		test( 'rejects when apiFetch throws an error during create', async () => {
			const apiError = new Error( 'Network error' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( upsertTicket( mockTicketData ) ).rejects.toThrow( 'Failed to create ticket: Network error' );
		} );

		test( 'rejects when apiFetch throws an error during update', async () => {
			const apiError = new Error( 'Network error' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( upsertTicket( mockUpdatedTicketData ) ).rejects.toThrow(
				'Failed to update ticket: Network error'
			);
		} );

		test( 'rejects when create response is not an object', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( 'not an object' );

			await expect( upsertTicket( mockTicketData ) ).rejects.toThrow(
				'Failed to create ticket: response did not return an object.'
			);
		} );

		test( 'rejects when update response is not an object', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( 'not an object' );

			await expect( upsertTicket( mockUpdatedTicketData ) ).rejects.toThrow(
				'Failed to update ticket: response did not return an object.'
			);
		} );

		test( 'rejects when create response is null', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( null );

			await expect( upsertTicket( mockTicketData ) ).rejects.toThrow(
				'Failed to create ticket: response did not return an object.'
			);
		} );

		test( 'rejects when update response is null', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( null );

			await expect( upsertTicket( mockUpdatedTicketData ) ).rejects.toThrow(
				'Failed to update ticket: response did not return an object.'
			);
		} );

		test( 'rejects when create response is undefined', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( undefined );

			await expect( upsertTicket( mockTicketData ) ).rejects.toThrow(
				'Failed to create ticket: response did not return an object.'
			);
		} );

		test( 'rejects when update response is undefined', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( undefined );

			await expect( upsertTicket( mockUpdatedTicketData ) ).rejects.toThrow(
				'Failed to update ticket: response did not return an object.'
			);
		} );

		test( 'handles ticket with sale price data', async () => {
			const ticketWithSalePrice = makeMockTicketDataWithSalePrice();

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			await upsertTicket( ticketWithSalePrice );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					sale_price: 15,
					sale_price_start_date: '2024-01-01',
					sale_price_end_date: '2024-12-31',
				} ),
			} );
		} );

		test( 'handles ticket with available dates', async () => {
			const ticketWithDates = makeMockTicketDataWithDates();

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			await upsertTicket( ticketWithDates );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					start_date: '2024-06-01 10:00:00',
					end_date: '2024-06-01 18:00:00',
				} ),
			} );
		} );

		test( 'handles ticket with IAC', async () => {
			const ticketWithIAC = makeMockTicketDataWithIAC();

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			await upsertTicket( ticketWithIAC );

			// IAC is not directly supported in the API, so it should not be in the request
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.not.objectContaining( {
					iac: 'ABC123',
				} ),
			} );
		} );

		test( 'handles ticket with menu order', async () => {
			const ticketWithMenuOrder = makeMockTicketDataWithMenuOrder();

			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			await upsertTicket( ticketWithMenuOrder );

			// Menu order is not directly supported in the API, so it should not be in the request
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.not.objectContaining( {
					menu_order: '5',
				} ),
			} );
		} );
	} );
} );
