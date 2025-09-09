import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, jest, test } from '@jest/globals';
import apiFetch from '@wordpress/api-fetch';
import { fetchTickets, fetchTicketsForPost, upsertTicket, deleteTicket } from '@tec/tickets/classy/api';
import { makeMockApiResponse, makeMockApiTickets } from '../_support/mockApiResponse';
import { createExpectedPath, TEST_CONSTANTS } from '../_support/testHelpers';
import { CurrencyPosition } from '@tec/common/classy/types/Currency';
import { TicketType } from '@tec/tickets/classy/types/Ticket';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

// Mock data structures for expected results
const mockMappedTickets = [
	{
		id: 1,
		eventId: 123,
		name: 'Sample Ticket 1',
		description: 'This is a sample ticket description for ticket 1.',
		cost: '25',
		costDetails: {
			code: 'USD',
			symbol: '$',
			position: 'prefix' as CurrencyPosition,
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
		type: 'default' as TicketType,
		availableFrom: '',
		availableUntil: '',
		menuOrder: 0,
	},
	{
		id: 2,
		eventId: 123,
		name: 'Sample Ticket 2',
		description: 'This is a sample ticket description for ticket 2.',
		cost: '25',
		costDetails: {
			code: 'USD',
			symbol: '$',
			position: 'prefix' as CurrencyPosition,
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
		type: 'default' as TicketType,
		availableFrom: '',
		availableUntil: '',
		menuOrder: 0,
	},
];

const mockTicketDataForCreate = {
	// 0 indicates create operation
	id: 0,
	eventId: 123,
	name: 'Sample Ticket 1',
	description: 'This is a sample ticket description for ticket 1.',
	cost: '25.00',
	costDetails: {
		code: 'USD',
		symbol: '$',
		position: 'prefix' as CurrencyPosition,
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
		isShared: false,
	},
	fees: {
		availableFees: [],
		automaticFees: [],
		selectedFees: [],
	},
	provider: 'tc',
	type: 'default' as TicketType,
};

const mockTicketDataForUpdate = {
	...mockTicketDataForCreate,
	// Non-zero indicates update operation
	id: 1,
};

const mockTicketDataWithSalePrice = {
	...mockTicketDataForCreate,
	salePriceData: {
		enabled: true,
		salePrice: '15.00',
		startDate: '2024-01-01',
		endDate: '2024-12-31',
	},
};

const mockTicketDataWithDates = {
	...mockTicketDataForCreate,
	availableFrom: '2024-06-01T10:00:00.000Z',
	availableUntil: '2024-06-01T18:00:00.000Z',
};

const mockTicketDataWithMenuOrder = {
	...mockTicketDataForCreate,
	menuOrder: 5,
};

const mockExpectedResult = {
	id: 1,
	eventId: 123,
	name: 'Sample Ticket 1',
	description: 'This is a sample ticket description for ticket 1.',
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
	type: 'default' as TicketType,
	availableFrom: '',
	availableUntil: '',
	menuOrder: 0,
};

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
		const mockApiTickets = makeMockApiTickets( 2 );
		const mockApiMappedTickets = mockMappedTickets;

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
			const expectedResult = [];

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
		const mockTicketData = mockTicketDataForCreate;
		const mockUpdatedTicketData = mockTicketDataForUpdate;
		const mockApiResponse = makeMockApiResponse();

		test( 'creates a new ticket successfully', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( mockApiResponse );

			const result = await upsertTicket( mockTicketData );

			expect( result ).toEqual( mockExpectedResult );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: restEndpoint,
				method: 'POST',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					title: 'Sample Ticket 1',
					content: 'This is a sample ticket description for ticket 1.',
					event: 123,
					price: 25,
					type: 'default' as TicketType,
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

			expect( result ).toEqual( mockExpectedResult );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: `${ restEndpoint }/1`,
				method: 'PUT',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: expect.objectContaining( {
					title: 'Sample Ticket 1',
					content: 'This is a sample ticket description for ticket 1.',
					event: 123,
					price: 25,
					type: 'default' as TicketType,
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
			const ticketWithSalePrice = mockTicketDataWithSalePrice;

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
			const ticketWithDates = mockTicketDataWithDates;

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

		test( 'handles ticket with menu order', async () => {
			const ticketWithMenuOrder = mockTicketDataWithMenuOrder;

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

	describe( 'deleteTicket', () => {
		test( 'deletes a ticket successfully', async () => {
			// @ts-ignore
			( apiFetch as jest.Mock ).mockResolvedValueOnce( undefined );

			await deleteTicket( 123 );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: `${ restEndpoint }/123`,
				method: 'DELETE',
				headers: expect.objectContaining( {
					'X-TEC-EEA': tecExperimentalHeader,
				} ),
				data: {
					remove_ticket_nonce: expect.any( String ),
				},
			} );
		} );

		test( 'rejects when apiFetch throws an error', async () => {
			const apiError = new Error( 'Network error' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Network error' );
		} );

		test( 'rejects when ticket ID is invalid', async () => {
			const apiError = new Error( 'Ticket not found' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 999 ) ).rejects.toThrow( 'Failed to delete ticket: Ticket not found' );
		} );

		test( 'rejects when server returns 500 error', async () => {
			const apiError = new Error( 'Internal Server Error' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Internal Server Error' );
		} );

		test( 'rejects when server returns 403 error', async () => {
			const apiError = new Error( 'Forbidden' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Forbidden' );
		} );

		test( 'rejects when server returns 401 error', async () => {
			const apiError = new Error( 'Unauthorized' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Unauthorized' );
		} );

		test( 'rejects when server returns 400 error', async () => {
			const apiError = new Error( 'Bad Request' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Bad Request' );
		} );

		test( 'rejects when server returns 404 error', async () => {
			const apiError = new Error( 'Not Found' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Not Found' );
		} );

		test( 'rejects when server returns timeout error', async () => {
			const apiError = new Error( 'Request timeout' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Request timeout' );
		} );

		test( 'rejects when server returns connection error', async () => {
			const apiError = new Error( 'Connection refused' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow( 'Failed to delete ticket: Connection refused' );
		} );

		test( 'rejects when server returns JSON parse error', async () => {
			const apiError = new Error( 'Unexpected token < in JSON at position 0' );
			// @ts-ignore
			( apiFetch as jest.Mock ).mockRejectedValueOnce( apiError );

			await expect( deleteTicket( 123 ) ).rejects.toThrow(
				'Failed to delete ticket: Unexpected token < in JSON at position 0'
			);
		} );
	} );
} );
