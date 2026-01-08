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
export const KEY_TICKETS_LIST = '_tribe_tickets_list';

export const KEY_TICKET_GOING_COUNT = '_tribe_ticket_going_count';
export const KEY_TICKET_NOT_GOING_COUNT = '_tribe_ticket_not_going_count';
export const KEY_TICKET_HAS_ATTENDEE_INFO_FIELDS = '_tribe_ticket_has_attendee_info_fields';

/**
 * Normalize a title field from an API response.
 * Handles cases where the API returns an object with raw/rendered properties.
 *
 * @since TBD
 *
 * @param {string|Object|null|undefined} value The title value from the API.
 *
 * @return {string} The normalized title string.
 */
export const normalizeTitle = ( value ) => {
	if ( value && typeof value === 'object' ) {
		return value.raw || '';
	}
	return value || '';
};

/**
 * Normalize a description field from an API response.
 * Handles cases where the API returns an object with raw/rendered properties.
 * Falls back to excerpt if description is empty.
 *
 * @since TBD
 *
 * @param {string|Object|null|undefined} value   The description value from the API.
 * @param {string}                       excerpt Optional excerpt to use as fallback.
 *
 * @return {string} The normalized description string.
 */
export const normalizeDescription = ( value, excerpt = '' ) => {
	if ( value && typeof value === 'object' ) {
		return value.raw || '';
	}
	return value || excerpt || '';
};
