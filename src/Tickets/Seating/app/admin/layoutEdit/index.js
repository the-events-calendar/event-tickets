import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	registerAction,
	RESERVATIONS_DELETED,
	SEAT_TYPES_UPDATED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
} from '@tec/tickets/seating/service/api';
import {
	ACTION_DELETE_RESERVATIONS,
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ajaxNonce,
	ajaxUrl,
} from '@tec/tickets/seating/ajax';

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
		return 0;
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
			updatedattendees: 0,
		}
	);
}

/**
 * Initializes iframe and the communication with the service.
 *
 * @since TBD
 *
 * @param {HTMLDocument|null} dom The document to use to search for the iframe element.
 *
 * @return {Promise<void>} A promise that resolves when the iframe is initialized.
 */
export async function init(dom) {
	dom = dom || document;

	registerAction(RESERVATIONS_DELETED, (data) =>
		handleReservationsDeleted(data.ids || [])
	);

	registerAction(SEAT_TYPES_UPDATED, (data) =>
		handleSeatTypesUpdated(data.seatTypes || [])
	);

	registerAction(RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES, (data) =>
		handleReservationsUpdatedFollowingSeatTypes(data.updated || {})
	);

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
