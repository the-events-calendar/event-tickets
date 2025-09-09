import { PartialTicket, Ticket } from '../types/Ticket';

let defaultTicket: Ticket = {
	// API response fields.
	id: 0,
	eventId: 0,
	provider: '',
	type: 'default',
	globalId: '',
	globalIdLineage: [],
	title: '',
	description: '',
	image: false,

	// Availability.
	availableFrom: '',
	availableFromDetails: {
		year: 0,
		month: 1,
		day: 1,
	},
	availableUntil: '',
	availableUntilDetails: {
		year: 0,
		month: 1,
		day: 1,
	},
	isAvailable: false,
	onSale: false,

	// Capacity.
	capacity: 0,
	capacityDetails: {
		available: 0,
		availablePercentage: 0,
		max: 0,
		sold: 0,
		pending: 0,
		globalStockMode: 'own',
	},

	// Pricing.
	cost: '',
	costDetails: {
		symbol: '',
		position: 'prefix',
		decimalSeparator: '',
		thousandSeparator: '',
		value: 0,
		precision: 2,
	},
	price: '',
	priceSuffix: null,

	// Sale price.
	salePriceData: {
		enabled: false,
		endDate: '',
		salePrice: '',
		startDate: '',
	},

	// Features.
	supportsAttendeeInformation: false,
	iac: '',

	// Attendees and checkin.
	attendees: [],
	checkin: {
		checkedIn: 0,
		uncheckedIn: 0,
		checkedInPercentage: 0,
		uncheckedInPercentage: 0,
	},

	// Fees.
	fees: {
		availableFees: [],
		automaticFees: [],
		selectedFees: [],
	},
};

/**
 * Combines a Ticket object with a PartialTicket object, merging their properties.
 *
 * @since TBD
 *
 * @param {Ticket} ticket1 The base Ticket object.
 * @param {PartialTicket} ticket2 The PartialTicket object to merge with the base Ticket.
 * @return {Ticket} The combined Ticket object with merged properties.
 */
const combineTicketWithPartial = ( ticket1: Ticket, ticket2: PartialTicket ): Ticket => {
	return {
		...ticket1,
		...ticket2,
		availableFromDetails: {
			...ticket1.availableFromDetails,
			...( ticket2?.availableFromDetails || {} ),
		},
		availableUntilDetails: {
			...ticket1.availableUntilDetails,
			...( ticket2?.availableUntilDetails || {} ),
		},
		capacityDetails: {
			...ticket1.capacityDetails,
			...( ticket2?.capacityDetails || {} ),
		},
		costDetails: {
			...ticket1.costDetails,
			...( ticket2?.costDetails || {} ),
		},
		salePriceData: {
			...ticket1.salePriceData,
			...( ticket2?.salePriceData || {} ),
		},
		checkin: {
			...ticket1.checkin,
			...( ticket2?.checkin || {} ),
		},
		fees: {
			...ticket1.fees,
			...( ticket2?.fees || {} ),
		},
	};
};

/**
 * Sets the default ticket object with the provided ticket data.
 *
 * This will merge the provided ticket data with the default ticket structure,
 * ensuring that all necessary fields are populated with default values.
 *
 * @since TBD
 *
 * @param {Ticket|PartialTicket} ticket The ticket object or partial ticket data to set as default.
 */
export const setDefaultTicket = ( ticket: Ticket | PartialTicket ): void => {
	defaultTicket = combineTicketWithPartial( defaultTicket, ticket );
};

/**
 * Hydrates a PartialTicket object to ensure all fields are populated with default values.
 *
 * @since TBD
 *
 * @param {PartialTicket} ticket The PartialTicket object to hydrate.
 * @return {Ticket} The hydrated Ticket object with all fields populated.
 */
export const hydrateTicket = ( ticket: PartialTicket ): Ticket => {
	return combineTicketWithPartial( defaultTicket, ticket );
};
