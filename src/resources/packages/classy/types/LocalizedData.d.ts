import { Currency } from '@tec/common/classy/types/Currency';
import { TecGlobal } from '@tec/common/classy/types/LocalizedData';

/**
 * The types of nonces used in the Classy Tickets application.
 *
 * @since TBD
 */
export type NonceTypes = 'deleteTicket' | 'createTicket' | 'updateTicket';

/**
 * The actions that can be performed with nonces in the Classy Tickets application.
 *
 * @since TBD
 */
export type NonceAction = 'add_ticket_nonce' | 'edit_ticket_nonce' | 'remove_ticket_nonce';

/**
 * The settings for the currency used in the Classy Tickets application.
 *
 * This type defines the structure of the currency settings, including the symbol,
 * decimal separator, thousand separator, position of the currency symbol, and precision.
 *
 * @since TBD
 */
export type CurrencySettings = Currency & {
	/**
	 * The character used to separate decimal values, e.g., '.', ','.
	 *
	 * @since TBD
	 */
	decimalSeparator: string;

	/**
	 * The character used to separate thousands, e.g., ',', '.'.
	 *
	 * @since TBD
	 */
	thousandSeparator: string;

	/**
	 * The number of decimal places to display.
	 *
	 * @since TBD
	 */
	precision: number;
};

/**
 * The settings for the Classy Tickets application.
 *
 * @since TBD
 */
export type Settings = {
	currency: CurrencySettings;
	startOfWeek: number;
	ticketPostTypes: string[];
};

/**
 * The localized data for the Classy Tickets application.
 *
 * This type includes settings and nonces used in the application.
 *
 * @since TBD
 */
export type LocalizedData = {
	settings: Settings;
	nonces: Record< NonceTypes, string >;
};

/**
 * The global type for the Classy Tickets application.
 *
 * @since TBD
 */
export type ETClassyGlobal = TecGlobal & {
	tickets: {
		classy: {
			data: LocalizedData;
		};
	};
};
