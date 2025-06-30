import { Capacity } from './Capacity';
import { CostDetails } from './CostDetails';
import { Fee } from './Fee';
import { NumericRange } from '@tec/common/classy/types/NumericRange';
import { TicketDate } from './TicketDate';
import { TicketType } from './TicketType';

type Percentage = NumericRange<0, 100>;

type CapacityDetails = {
	available: number;
	availablePercentage: Percentage;
	max: number;
	sold: number;
	pending: number;
	globalStockMode: GlobalStockMode;
}

type GlobalStockMode = 'own' | 'capped' | 'global';

type SalePriceDetails = {
	enabled: boolean;
	endDate: string;
	salePrice: string;
	startDate: string;
}

type CheckinDetails = {
	checkedIn: number;
	uncheckedIn: number;
	checkedInPercentage: Percentage;
	uncheckedInPercentage: Percentage;
}

type FeesData = {
	availableFees: Fee[];
	automaticFees: Fee[];
	selectedFees: Fee[];
}

export type Ticket = {
	// API response fields
	id: number;
	eventId: number;
	provider: string;
	type: TicketType;
	globalId: string;
	globalIdLineage: string[];
	author: string;
	status: string;
	date: string;
	dateUtc: string;
	modified: string;
	modifiedUtc: string;
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

	// URLs
	restUrl: string;
};
