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
} from '@tec/tickets/seating/service/api';
import {
	ACTION_DELETE_RESERVATIONS,
	ACTION_SEAT_TYPES_UPDATED,
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
 * Handles the seat types updated action.
 *
 * @since TBD
 *
 * @param {UpdatedSeatType[]} updatedSeatTypes The updated seat types.
 *
 * @return {Promise<number|false>} A promise that will resolve to the number of seat types updated or `false` on failure.
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
		console.error('Failed to update seat types');
		return false;
	}

	const json = await response.json();

	return json?.data?.numberUpdated || 0;
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

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
