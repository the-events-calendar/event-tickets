import { CostDetails } from './CostDetails';
import { Fee } from './Fee';
import { NumericRange } from '@tec/common/classy/types/NumericRange';
import { Days } from '@tec/common/classy/types/Days';
import { Hours } from '@tec/common/classy/types/Hours';
import { Minutes } from '@tec/common/classy/types/Minutes';
import { Months } from '@tec/common/classy/types/Months';

// These types are simple aliases and do not require export statements.
type Seconds = Minutes;
type Percentage = NumericRange<0, 100>;

export type Seating = 'general-admission' | 'assigned-seating';
export type GlobalStockMode = 'own' | 'capped' | 'global';
export type TicketType = 'default' | 'rsvp';

export type CapacityDetails = {
	available?: number;
	availablePercentage?: Percentage;
	globalStockMode: GlobalStockMode;
	max: number;
	sold?: number;
	pending?: number;
}

/**
 * Represents the capacity settings for a ticket.
 *
 * @typedef {Object} CapacitySettings
 * @property {Seating} [admissionType] - The type of admission (general admission or assigned seating).
 * @property {number | ''} enteredCapacity - The capacity entered by the user, can be a number or an empty string.
 * @property {number | ''} [displayedCapacity] - The capacity displayed to the user, can be a number or an empty string.
 * @property {boolean} isShared - Indicates if the capacity is shared across multiple tickets.
 */
export type CapacitySettings = {
	admissionType?: Seating;
	displayedCapacity?: number | '';
	enteredCapacity: number | '';
	isShared: boolean;
	sharedCapacity?: number;
	globalStockMode?: GlobalStockMode;
}

export type CheckinDetails = {
	checkedIn: number;
	uncheckedIn: number;
	checkedInPercentage?: Percentage;
	uncheckedInPercentage?: Percentage;
}

type FeeDataKeys = 'availableFees' | 'automaticFees' | 'selectedFees';

export type FeesData = Record<FeeDataKeys, Fee[]>;

export type SalePriceDetails = {
	enabled: boolean;
	endDate: string;
	salePrice: string;
	startDate: string;
}

export type TicketDate = {
	year: number;
	month: Months;
	day: Days;
	hour?: Hours;
	minute?: Minutes;
	second?: Seconds;
}

// todo: Some kind of positive number type for TicketId.
export type TicketId = number;

export type TicketSettings = {
	id?: TicketId;
	eventId?: number;
	name: string;
	description?: string;
	cost?: string;
	costDetails?: CostDetails;
	salePriceData?: SalePriceDetails;
	capacitySettings?: CapacitySettings;
	fees?: FeesData;
	provider?: string;
	type?: TicketType;
	menuOrder?: number;

	// Ticket sale dates.
	availableFrom?: string;
	availableFromDetails?: TicketDate;
	availableUntil?: string;
	availableUntilDetails?: TicketDate;

	// Features.
	supportsAttendeeInformation?: boolean;
	iac?: string;
}

export type Ticket = {
	// API response fields.
	id: TicketId;
	eventId: number;
	provider: string;
	type: TicketType;
	globalId: string;
	globalIdLineage: string[];
	title: string;
	description: string;
	image: boolean | string;
	menuOrder?: number;

	// Availability.
	availableFrom: string;
	availableFromDetails: TicketDate;
	availableUntil: string;
	availableUntilDetails: TicketDate;
	isAvailable: boolean;
	onSale: boolean;

	// Capacity.
	capacity: number | '';
	capacityDetails: CapacityDetails;

	// Pricing.
	cost: string;
	costDetails: CostDetails;
	price: number | string;
	priceSuffix: string | null;

	// Sale price.
	salePriceData: SalePriceDetails;

	// Features.
	supportsAttendeeInformation: boolean;
	iac: string;

	// Attendees and checkin.
	attendees: any[];
	checkin: CheckinDetails;

	// Fees.
	fees: FeesData;
};


export type PartialTicket = Partial<Ticket>;
