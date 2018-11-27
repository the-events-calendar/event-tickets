/**
 * External dependencies
 */
import { string, globals } from '@moderntribe/common/utils';

const { settings, priceSettings, tickets: ticketsConfig } = globals;
/**
 * Get currency symbol by provider
 */
export const getProviderCurrency = ( provider ) => {
	const tickets = ticketsConfig();
	const providers = tickets.providers || {};

	// if we don't get the provider, return the default one
	if ( '' === provider ) {
		return tickets.default_currency;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency : tickets.default_currency;
};

/**
 * Get the default provider's currency symbol
 */
export const getDefaultProviderCurrency = () => {
	const tickets = ticketsConfig();
	const defaultProvider = tickets.default_provider || '';

	return getProviderCurrency( defaultProvider );
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
