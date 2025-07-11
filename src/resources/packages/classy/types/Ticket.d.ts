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

export type Capacity = 'general-admission' | 'assigned-seating';
export type GlobalStockMode = 'own' | 'capped' | 'global';
export type TicketType = 'default' | 'rsvp';

export type CapacityDetails = {
	available: number;
	availablePercentage: Percentage;
	max: number;
	sold: number;
	pending: number;
	globalStockMode: GlobalStockMode;
}

export type CheckinDetails = {
	checkedIn: number;
	uncheckedIn: number;
	checkedInPercentage: Percentage;
	uncheckedInPercentage: Percentage;
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

export type Ticket = {
	// API response fields
	id: number;
	eventId: number;
	provider: string;
	type: TicketType;
	globalId: string;
	globalIdLineage: string[];
	title: string;
	description: string;
	image: boolean | string;

	// Availability
	availableFrom: string;
	availableFromDetails: TicketDate;
	availableUntil: string;
	availableUntilDetails: TicketDate;
	isAvailable: boolean;
	onSale: boolean;

	// Capacity
	capacity: number;
	capacityDetails: CapacityDetails;

	// Pricing
	cost: string;
	costDetails: CostDetails;
	priceSuffix: string | null;

	// Sale price
	salePriceData: SalePriceDetails;

	// Features
	supportsAttendeeInformation: boolean;
	iac: string;

	// Attendees and checkin
	attendees: any[];
	checkin: CheckinDetails;

	// Fees
	fees: FeesData;
};


export type PartialTicket = Partial<Ticket>;
