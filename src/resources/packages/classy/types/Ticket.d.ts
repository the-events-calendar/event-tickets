import { CostDetails } from './CostDetails';
import { Fee } from './Fee';
import { NumericRange } from '@tec/common/classy/types/NumericRange';
import { Days } from '@tec/common/classy/types/Days';
import { Hours } from '@tec/common/classy/types/Hours';
import { Minutes } from '@tec/common/classy/types/Minutes';
import { Months } from '@tec/common/classy/types/Months';

// These types are simple aliases and do not require export statements.
type Seconds = Minutes;
type Percentage = NumericRange< 0, 100 >;

export type Seating = 'general-admission' | 'assigned-seating';
export type TicketType = 'default' | 'rsvp';

export type CapacitySettings = {
	admissionType?: Seating;
	displayedCapacity?: number | '';
	enteredCapacity: number | '';
};

type FeeDataKeys = 'availableFees' | 'automaticFees' | 'selectedFees';

export type FeesData = Record< FeeDataKeys, Fee[] >;

export type SalePriceDetails = {
	enabled: boolean;
	endDate: Date | '';
	salePrice: string;
	startDate: Date | '';
};

export type TicketDate = {
	year: number;
	month: Months;
	day: Days;
	hour?: Hours;
	minute?: Minutes;
	second?: Seconds;
};

// todo: Some kind of positive number type for TicketId.
export type TicketId = number;

/**
 * The structure of a single ticket within the Classy editor.
 */
export type TicketSettings = {
	/**
	 * The unique identifier for the ticket.
	 */
	id?: TicketId;

	/**
	 * The event ID this ticket is associated with.
	 */
	eventId?: number;

	/**
	 * The ticket's name. Used for the "Title" field in the editor.
	 */
	name: string;

	/**
	 * A description of the ticket.
	 */
	description?: string;

	/**
	 * Whether to show the description in the ticket listing.
	 */
	showDescription?: boolean;

	/**
	 * The cost of the ticket, as a formatted string (e.g. "20.00").
	 */
	cost?: string;

	/**
	 * The cost details of the ticket, including currency and value.
	 */
	costDetails?: CostDetails;

	/**
	 * The details for an on-sale price for the ticket.
	 */
	salePriceData?: SalePriceDetails;

	/**
	 * The capacity settings for the ticket.
	 */
	capacitySettings?: CapacitySettings;
	fees?: FeesData;
	provider?: string;
	type?: TicketType;
	menuOrder?: number;

	// Ticket sale dates.
	availableFrom?: Date | '';
	availableUntil?: Date | '';

	// Features.
	supportsAttendeeInformation?: boolean;

	/**
	 * The SKU of the ticket.
	 */
	sku?: string;
};
