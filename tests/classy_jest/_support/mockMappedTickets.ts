import { TicketSettings } from '@tec/tickets/classy/types/Ticket';

/**
 * Mock mapped tickets data for testing.
 *
 * @return {TicketSettings[]} The mock mapped tickets array.
 */
export function makeMockMappedTickets(): TicketSettings[] {
	return [
		{
			id: 1,
			eventId: 123,
			name: 'Sample Ticket',
			description: 'This is a sample ticket description.',
			cost: '50',
			costDetails: {
				symbol: '$',
				position: 'prefix',
				decimalSeparator: '.',
				thousandSeparator: ',',
				suffix: '',
				precision: 2,
				value: 50,
			},
			salePriceData: {
				enabled: true,
				salePrice: '20',
				startDate: '2024-01-01',
				endDate: '2024-12-31',
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
			availableFrom: '2024-06-01 10:00:00',
			availableUntil: '2024-06-01 18:00:00',
			iac: '',
			menuOrder: 0,
		},
	];
}
