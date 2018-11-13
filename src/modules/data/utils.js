import { globals } from '@moderntribe/common/utils';

const { config } = globals;

export const PREFIX_TICKETS_STORE = '@@MT/TICKETS';

export const RSVP_POST_TYPE = 'tribe_rsvp_tickets';

/**
 * @todo: these are expected to change based on BE changes
 */
export const KEY_RSVP_FOR_EVENT = '_tribe_rsvp_for_event';
export const KEY_TICKET_SHOW_DESCRIPTION = '_tribe_ticket_show_description';
export const KEY_PRICE = '_price';
export const KEY_TICKET_CAPACITY = '_tribe_ticket_capacity';
export const KEY_TICKET_START_DATE = '_ticket_start_date';
export const KEY_TICKET_END_DATE = '_ticket_end_date';
export const KEY_TICKET_SHOW_NOT_GOING = '_tribe_ticket_show_not_going';
export const KEY_TICKET_HEADER = '_tribe_ticket_header';
export const KEY_TICKET_DEFAULT_PROVIDER = '_tribe_default_ticket_provider';

export const KEY_TICKET_GOING_COUNT = '_tribe_ticket_going_count';
export const KEY_TICKET_NOT_GOING_COUNT = '_tribe_ticket_not_going_count';

export const TICKET_TYPES_VALUES = [ 'unlimited', 'capped', 'own' ];

export const TICKET_TYPES = {
	unlimited: TICKET_TYPES_VALUES[ 0 ],
	shared: TICKET_TYPES_VALUES[ 1 ],
	independent: TICKET_TYPES_VALUES[ 2 ],
};

export const TICKET_ORDERS_PAGE_SLUG = {
	Tribe__Tickets__Commerce__PayPal__Main: 'tpp-orders',
	Tribe__Tickets_Plus__Commerce__WooCommerce__Main: 'tickets-orders',
};

/**
 * Get currency symbol by provider
 */
export function getProviderCurrency( provider ) {
	const tickets = config().tickets || {};
	const providers = tickets.providers || {};

	// if we don't get the provider, return the default one
	if ( '' == provider ) {
		return tickets.default_currency;
	}

	const [ result ] = providers.filter( el => el.class === provider );
	return result ? result.currency : tickets.default_currency;
};

/**
 * Get the default provider's currency symbol
 */
export function getDefaultProviderCurrency() {
	const tickets = config().tickets || {};
	const defaultProvider = tickets.default_provider || '';

	return getProviderCurrency( defaultProvider );
}

