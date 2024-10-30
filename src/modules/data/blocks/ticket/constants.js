export const TC = 'tribe-commerce';
export const EDD = 'edd';
export const WOO = 'woo';
export const RSVP = 'rsvp';

export const RSVP_CLASS = 'Tribe__Tickets__RSVP';
export const TICKETS_COMMERCE_MODULE_CLASS = 'TEC\\Tickets\\Commerce\\Module';
export const TC_CLASS = 'Tribe__Tickets__Commerce__PayPal__Main';
export const EDD_CLASS = 'Tribe__Tickets_Plus__Commerce__EDD__Main';
export const WOO_CLASS = 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main';

export const PROVIDER_CLASS_TO_PROVIDER_MAPPING = {
	[ TC_CLASS ]: TC,
	[ EDD_CLASS ]: EDD,
	[ WOO_CLASS ]: WOO,
};

export const PROVIDER_TYPES = [ TC, EDD, WOO ];

export const EDD_ORDERS = 'edd-orders';
export const TC_ORDERS = 'tpp-orders';
export const WOO_ORDERS = 'tickets-orders';
export const TICKETS_COMMERCE_ORDERS = 'tickets-commerce-orders';

export const TICKET_ORDERS_PAGE_SLUG = {
	[ EDD_CLASS ]: EDD_ORDERS,
	[ TC_CLASS ]: TC_ORDERS,
	[ WOO_CLASS ]: WOO_ORDERS,
	[ TICKETS_COMMERCE_MODULE_CLASS ]: TICKETS_COMMERCE_ORDERS,
};

export const UNLIMITED = 'unlimited';
export const SHARED = 'shared';
export const GLOBAL = 'global'; // The name used by the backend to indicate shared capacity.
export const INDEPENDENT = 'independent';
export const CAPPED = 'capped';
export const OWN = 'own';

export const TICKET_TYPES_VALUES = [ UNLIMITED, CAPPED, OWN ];

export const TICKET_TYPES = {
	[ UNLIMITED ]: UNLIMITED,
	[ SHARED ]: CAPPED,
	[ INDEPENDENT ]: OWN,
};

export const PREFIX = 'prefix';
export const SUFFIX = 'postfix';

export const PRICE_POSITIONS = [ PREFIX, SUFFIX ];

// eslint-disable-next-line no-undef
export const TICKET_LABELS = window?.tribe_editor_config?.tickets?.ticketLabels;
export const SALE_PRICE_LABELS = window?.tribe_editor_config?.tickets?.salePrice;

// eslint-disable-next-line max-len
export const IS_FREE_TC_TICKET_ALLOWED = window?.tribe_editor_config?.tickets?.commerce?.isFreeTicketAllowed;
