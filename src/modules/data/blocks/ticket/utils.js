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
 * Get the default provider's currency symbol
 */
export const getDefaultProviderCurrency = () => {
	return getProviderCurrency( getDefaultTicketProvider() );
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
