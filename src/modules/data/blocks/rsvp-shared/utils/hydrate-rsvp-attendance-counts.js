/**
 * Parses attendance counts from a V2 TEC ticket REST response.
 *
 * Only includes fields present on the ticket so partial API responses do not
 * reset counts back to zero.
 *
 * @param {Object} ticket Ticket REST response object.
 * @return {{ goingCount?: number, notGoingCount?: number, inventory?: number|null }} Parsed counts.
 */
export const getAttendanceCountsFromV2Ticket = ( ticket ) => {
	if ( ! ticket ) {
		return {};
	}

	const counts = {};

	if ( ticket.going_count != null || ticket.sold != null ) {
		counts.goingCount = parseInt( ticket.going_count ?? ticket.sold ?? 0, 10 ) || 0;
	}

	if ( ticket.not_going_count != null ) {
		counts.notGoingCount = parseInt( ticket.not_going_count, 10 ) || 0;
	}

	if ( ticket.stock != null && Number( ticket.stock ) >= 0 ) {
		counts.inventory = parseInt( ticket.stock, 10 );
	}

	return counts;
};

/**
 * Dispatches RSVP attendance count actions.
 *
 * @param {Function} dispatch Redux dispatch.
 * @param {Object}   actions  RSVP action creators.
 * @param {Object}   counts   Attendance count payload.
 * @param {number}   [counts.goingCount]    Number of going responses.
 * @param {number}   [counts.notGoingCount] Number of not-going responses.
 * @param {number|null} [counts.inventory] Remaining inventory from the API, when available.
 */
export const hydrateRsvpAttendanceCounts = ( dispatch, actions, counts = {} ) => {
	if ( counts.goingCount !== undefined ) {
		dispatch( actions.setRSVPGoingCount( counts.goingCount ) );
	}

	if ( counts.notGoingCount !== undefined ) {
		dispatch( actions.setRSVPNotGoingCount( counts.notGoingCount ) );
	}

	if ( counts.inventory !== undefined ) {
		dispatch( actions.setRSVPInventory( counts.inventory ) );
	}
};
