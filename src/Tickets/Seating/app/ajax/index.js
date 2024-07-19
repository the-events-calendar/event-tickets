import { localizedData } from './localized-data';

const {
	ajaxUrl,
	ajaxNonce,
	ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
	ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
	ACTION_INVALIDATE_LAYOUTS_CACHE,
	ACTION_DELETE_MAP,
	ACTION_DELETE_LAYOUT,
	ACTION_POST_RESERVATIONS,
	ACTION_CLEAR_RESERVATIONS,
	ACTION_FETCH_ATTENDEES,
} = localizedData;

/**
 * @typedef {Object} SeatTypeData
 *
 */

/**
 * Fetches seat types for a given layout ID.
 *
 * @since TBD
 *
 * @param {string} layoutId The layout ID to fetch seat types for.
 *
 * @return {Promise<void>} A promise that will be resolved when the seat types are fetched.
 */
async function fetchSeatTypesByLayoutId(layoutId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('action', ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID);
	url.searchParams.set('layout', layoutId);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	const response = await fetch(url.toString(), {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
	});

	if (response.status !== 200) {
		throw new Error(
			`Failed to fetch seat types for layout ID ${layoutId}. Status: ${response.status}`
		);
	}

	const json = await response.json();

	return json?.data || [];
}

/**
 * Fetches attendees for a given post ID.
 *
 * @param {number} postId The post ID to fetch attendees for.
 *
 * @return {Promise<Attendee[]>} A promise that will be resolved with the attendees.
 */
export async function fetchAttendees(postId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('postId', postId);
	url.searchParams.set('action', ACTION_FETCH_ATTENDEES);
	const response = await fetch(url.toString(), {
		method: 'POST',
		headers: {
			Accept: 'application/json',
		},
	});

	const json = await response.json();

	if (response.status !== 200) {
		throw new Error(
			`Failed to fetch attendees for post ID ${postId}. Status: ${response.status} - ${json?.data?.error}`
		);
	}

	return json?.data || [];
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.ajax = window.tec.tickets.seating.ajax || {};
window.tec.tickets.seating.ajax = {
	...window.tec.tickets.seating.ajax,
	fetchSeatTypesByLayoutId,
	fetchAttendees,
};
