// Post types.
export const POST_TYPE_TICKET = 'tribe_tpp_ticket';

// Namespace for our hooks.
export const HOOKS_NAMESPACE = 'tec.classy.tickets';

/**
 * Metadata keys for the Classy editor.
 *
 * These keys are used to store ticket-related metadata in the WordPress editor.
 *
 * Note that for these keys to be saved correctly, they must be ALSO be registered
 * in the \Tribe\Tickets\Classy\Meta::META array.
 */
export const METADATA_TICKET_PRICE = '_ticket_price';
export const METADATA_TICKET_START_DATE = '_ticket_start_date';
export const METADATA_TICKET_END_DATE = '_ticket_end_date';
export const METADATA_TICKET_STOCK = '_ticket_stock';
export const METADATA_TICKET_SKU = '_ticket_sku';
export const METADATA_TICKET_CURRENCY = '_ticket_currency';
export const METADATA_TICKET_CURRENCY_POSITION = '_ticket_currency_position';
export const METADATA_TICKET_CURRENCY_SYMBOL = '_ticket_currency_symbol';
export const METADATA_TICKET_IS_FREE = '_ticket_is_free';
export const METADATA_TICKET_QUANTITY = '_ticket_quantity';
export const METADATA_TICKET_SALE_PRICE = '_ticket_sale_price';
export const METADATA_TICKET_SALE_START_DATE = '_ticket_sale_start_date';
export const METADATA_TICKET_SALE_END_DATE = '_ticket_sale_end_date';
