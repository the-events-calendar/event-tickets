import {
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_SEAT_TYPE_DELETED,
	ACTION_DELETE_RESERVATIONS,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ajaxNonce,
	ajaxUrl,
} from '@tec/tickets/seating/ajax';

/**
 * @typedef {Object} UpdatedSeatType
 * @property {string}            id               The seat type ID.
 * @property {string}            name             The seat type name.
 * @property {string}            mapId            The ID of the map the seat type belongs to.
 * @property {string}            layoutId         The ID of the layout the seat type belongs to.
 * @property {string}            description      The seat type description.
 * @property {number}            seatsCount       The seat type seats.
 *
 * @typedef {Object} UpdatedSeatTypeData
 * @property {UpdatedSeatType[]} seatTypes        The updated seat types.
 *
 *
 * @typedef {Object} SeatTypesUpdateResponseData
 * @property {number}            updatedSeatTypes The number of seat types updated.
 * @property {number}            updatedTickets   The number of tickets updated.
 * @property {number}            updatedPosts     The number of posts updated.
 */

/**
 * Handles the seat types updated action.
 *
 * @since 5.16.0
 *
 * @param {UpdatedSeatTypeData} data The updated seat types.
 *
 * @return {Promise<SeatTypesUpdateResponseData|false>} A promise that will resolve to the seat types update response
 *                                                      data or `false` on failure.
 */
export async function handleSeatTypesUpdated(data) {
	const updatedSeatTypes = data?.seatTypes || [];

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
 * @typedef {Object} SeatTypeDeletedData
 * @property {string}          deletedId  The ID of the seat type that was deleted.
 * @property {UpdatedSeatType} transferTo The seat type that was transferred to.
 */

/**
 * Handles the seat type deleted action.
 *
 * @since 5.16.0
 *
 * @param {SeatTypeDeletedData} data The deleted seat type data.
 *
 * @return {Promise<boolean>} A promise that will resolve to `true` if the seat type deletion handling was successful.
 */
export async function handleSeatTypeDeleted(data) {
	if (!data?.deletedId) {
		return false;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_SEAT_TYPE_DELETED);

	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(data),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	return json?.data || false;
}

/**
 * @typedef {Object} ReservationsDeletedData
 * @property {string[]} ids The IDs of the reservations that were deleted.
 */

/**
 * Handles the deletion of reservations.
 *
 * @since 5.16.0
 *
 * @param {ReservationsDeletedData} data The IDs of the reservations that were deleted.
 *
 * @return {Promise<boolean|number>} A promise that will resolve to the number of
 *                                   reservations that were deleted or `false` on failure.
 */
export async function handleReservationsDeleted(data) {
	const ids = data?.ids || [];

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
 * @property {number}               updatedAttendees The number of Attendees updated.
 *
 * @typedef {Object} ReservationsUpdateFollowingSeatTypesData
 * @property {Map<string,string[]>} updated          The updated reservations.
 */

/**
 * Handles the update of Reservations following a Seat Type update.
 *
 * @since 5.16.0
 *
 * @param {ReservationsUpdateFollowingSeatTypesData} data The updated reservations.
 *
 * @return {Promise<ReservationsUpdateResponseData|false>} A promise that will resolve to the reservations update
 *                                                         response data or `false` on failure.
 */
export async function handleReservationsUpdatedFollowingSeatTypes(data) {
	const updated = data?.updated || {};

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
