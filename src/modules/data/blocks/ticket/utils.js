/**
 * External dependencies
 */
import { string, globals } from '@moderntribe/common/utils';

const { settings, priceSettings, tickets: ticketsConfig } = globals;
/**
 * Internal dependencies
 */
import {
	getDefaultTicketProvider,
	getTicketProviders,
} from '@moderntribe/tickets/data/blocks/ticket/selectors';

/**
 * Get currency symbol by provider
 *
 * @param provider The tickets provider class
 */
export const getProviderCurrency = ( provider ) => {
	const tickets = ticketsConfig();
	const providers = getTicketProviders();

	// if we don't get the provider, return the default one
	if ( '' === provider ) {
		return tickets.default_currency;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency : tickets.default_currency;
};

/**
 * Get currency decimal point by provider
 *
 * @param provider The tickets provider class
 */
export const getProviderCurrencyDecimalPoint = ( provider ) => {
	const providers = getTicketProviders();
	const defaultCurrencyDecimalPoint = '.';

	// if we don't get the provider, return the default one
	if ( '' === provider ) {
		return defaultCurrencyDecimalPoint;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency_decimal_point : defaultCurrencyDecimalPoint;
};

/**
 * Get currency number of decimals by provider
 *
 * @param provider The tickets provider class
 */
export const getProviderCurrencyNumberOfDecimals = ( provider ) => {
	const providers = getTicketProviders();
	const defaultCurrencyNumberOfDecimals = 2;

	// if we don't get the provider, return the default one
	if ( '' === provider ) {
		return defaultCurrencyNumberOfDecimals;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency_number_of_decimals : defaultCurrencyNumberOfDecimals;
};

/**
 * Get currency thousands separator by provider
 *
 * @param provider The tickets provider class
 */
export const getProviderCurrencyThousandsSep = ( provider ) => {
	const providers = getTicketProviders();
	const defaultCurrencyThousandsSep = ',';

	// if we don't get the provider, return the default one
	if ( '' === provider ) {
		return defaultCurrencyThousandsSep;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency_thousands_sep : defaultCurrencyThousandsSep;
};

/**
 * Get the default provider's currency symbol
 */
export const getDefaultProviderCurrency = () => {
	return getProviderCurrency( getDefaultTicketProvider() );
};

/**
 * Get the default provider's currency decimal point
 */
export const getDefaultProviderCurrencyDecimalPoint = () => {
	return getProviderCurrencyDecimalPoint( getDefaultTicketProvider() );
};

/**
 * Get the default provider's currency number of decimals
 */
export const getDefaultProviderCurrencyNumberOfDecimals = () => {
	return getProviderCurrencyNumberOfDecimals( getDefaultTicketProvider() );
};

/**
 * Get the default provider's currency thousands separator
 */
export const getDefaultProviderCurrencyThousandsSep = () => {
	return getProviderCurrencyThousandsSep( getDefaultTicketProvider() );
};

/**
 * Get currency position
 */
export const getDefaultCurrencyPosition = () => {
	const position = string.isTruthy( settings() && settings().reverseCurrencyPosition )
		? 'suffix'
		: 'prefix';

	return priceSettings() && priceSettings().defaultCurrencyPosition
		? priceSettings().defaultCurrencyPosition
		: position;
};
