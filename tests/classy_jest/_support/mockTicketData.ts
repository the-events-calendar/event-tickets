import { TicketSettings } from '@tec/tickets/classy/types/Ticket';

/**
 * Mock ticket data for create operations (id: 0).
 *
 * @return {TicketSettings} The mock ticket data for creation.
 */
export function makeMockTicketDataForCreate(): TicketSettings {
	return {
		// 0 indicates create operation
		id: 0,
		eventId: 123,
		name: 'Test Ticket',
		description: 'Test ticket description',
		cost: '25.00',
		costDetails: {
			symbol: '$',
			position: 'prefix',
			decimalSeparator: '.',
			thousandSeparator: ',',
			suffix: '',
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
		type: 'default',
	};
}

/**
 * Mock ticket data for update operations (id: 1).
 *
 * @return {TicketSettings} The mock ticket data for updates.
 */
export function makeMockTicketDataForUpdate(): TicketSettings {
	return {
		...makeMockTicketDataForCreate(),
		// Non-zero indicates update operation
		id: 1,
	};
}

/**
 * Mock ticket data with sale price enabled.
 *
 * @return {TicketSettings} The mock ticket data with sale price.
 */
export function makeMockTicketDataWithSalePrice(): TicketSettings {
	return {
		...makeMockTicketDataForCreate(),
		salePriceData: {
			enabled: true,
			salePrice: '15.00',
			startDate: '2024-01-01',
			endDate: '2024-12-31',
		},
	};
}

/**
 * Mock ticket data with available dates.
 *
 * @return {TicketSettings} The mock ticket data with dates.
 */
export function makeMockTicketDataWithDates(): TicketSettings {
	return {
		...makeMockTicketDataForCreate(),
		availableFrom: '2024-06-01T10:00:00.000Z',
		availableUntil: '2024-06-01T18:00:00.000Z',
	};
}

/**
 * Mock ticket data with IAC.
 *
 * @return {TicketSettings} The mock ticket data with IAC.
 */
export function makeMockTicketDataWithIAC(): TicketSettings {
	return {
		...makeMockTicketDataForCreate(),
		iac: 'ABC123',
	};
}

/**
 * Mock ticket data with menu order.
 *
 * @return {TicketSettings} The mock ticket data with menu order.
 */
export function makeMockTicketDataWithMenuOrder(): TicketSettings {
	return {
		...makeMockTicketDataForCreate(),
		menuOrder: 5,
	};
}
