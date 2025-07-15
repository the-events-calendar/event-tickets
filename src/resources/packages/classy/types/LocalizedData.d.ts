import { TecGlobal } from '@tec/common/classy/types/LocalizedData';

/**
 * The types of nonces used in the Classy Tickets application.
 *
 * @since TBD
 */
export type NonceTypes = 'deleteTicket' | 'createTicket' | 'updateTicket';

/**
 * The settings for the Classy Tickets application.
 *
 * @since TBD
 */
export type Settings = {
	/**
	 * The currency symbol used for tickets.
	 *
	 * @since TBD
	 */
	currencySymbol?: string;

	/**
	 * The currency code used for tickets.
	 *
	 * @since TBD
	 */
	currencyCode?: string;

	/**
	 * The currency format used for tickets.
	 *
	 * @since TBD
	 */
	currencyFormat?: string;

	/**
	 * The number of decimal places used for tickets.
	 *
	 * @since TBD
	 */
	numberOfDecimals?: number;
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
	nonces: Record<NonceTypes, string>;
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
		}
	}
};
