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
			type: 'default',
			availableFrom: '',
			availableUntil: '',
			iac: '',
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
		},
	];
}
