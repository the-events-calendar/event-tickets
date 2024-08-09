import {
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_DELETE_RESERVATIONS,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ajaxNonce,
	ajaxUrl,
} from '@tec/tickets/seating/ajax';

/**
 * @typedef {Object} UpdatedSeatType
 * @property {string} id          The seat type ID.
 * @property {string} name        The seat type name.
 * @property {string} mapId       The ID of the map the seat type belongs to.
 * @property {string} layoutId    The ID of the layout the seat type belongs to.
 * @property {string} description The seat type description.
 * @property {number} seatsCount  The seat type seats.
 */

/**
 * @typedef {Object} SeatTypesUpdateResponseData
 * @property {number} updatedSeatTypes The number of seat types updated.
 * @property {number} updatedTickets   The number of tickets updated.
 * @property {number} updatedPosts     The number of posts updated.
 */

/**
 * Handles the seat types updated action.
 *
 * @since TBD
 *
 * @param {UpdatedSeatType[]} updatedSeatTypes The updated seat types.
 *
 * @return {Promise<SeatTypesUpdateResponseData|false>} A promise that will resolve to the seat types update response
 *                                                      data or `false` on failure.
 */
export async function handleSeatTypesUpdated(updatedSeatTypes) {
	if (!(Array.isArray(updatedSeatTypes) && updatedSeatTypes.length > 0)) {
		return false;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_SEAT_TYPES_UPDATED);
	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(updatedSeatTypes),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	return (
		json?.data || {
			updatedSeatTypes: 0,
			updatedTickets: 0,
			updatedPosts: 0,
		}
	);
}


/**
 * Handles the deletion of reservations.
 *
 * @since TBD
 *
 * @param {string[]} ids The IDs of the reservations that were deleted.
 *
 * @return {Promise<boolean|number>} A promise that will resolve to the number of
 *                                   reservations that were deleted or `false` on failure.
 */
export async function handleReservationsDeleted(ids) {
	if (!(Array.isArray(ids) && ids.length > 0)) {
		return 0;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_DELETE_RESERVATIONS);
	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(ids),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	return json?.data?.numberDeleted || 0;
}

/**
 * @typedef {Object} ReservationsUpdateResponseData
 * @property {number} updatedAttendees The number of Attendees updated.
 */

/**
 * Handles the update of Reservations following a Seat Type update.
 *
 * @since TBD
 *
 * @param {Map<string,string[]>} updated The updated reservations.
 *
 * @return {Promise<ReservationsUpdateResponseData|false>} A promise that will resolve to the reservations update
 *                                                         response data or `false` on failure.
 */
export async function handleReservationsUpdatedFollowingSeatTypes(updated) {
	if (!updated || Object.keys(updated).length === 0) {
		return 0;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES);
	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(updated),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	return (
		json?.data || {
			updatedAttendees: 0,
		}
	);
}
