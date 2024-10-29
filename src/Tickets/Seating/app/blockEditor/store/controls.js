import {
	ajaxUrl,
	ajaxNonce,
	ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
} from '@tec/tickets/seating/ajax';

/**
 * @typedef {Object} SeatTypeData
 * @property {string} id    The seat type ID.
 * @property {string} name  The seat type name.
 * @property {number} seats The number of seats in the seat type.
 */

/**
 * Fetches seat types for a given layout ID.
 *
 * @since 5.16.0
 *
 * @param {string} layoutId The layout ID to fetch seat types for.
 *
 * @return {Promise<SeatTypeData>} A promise that will be resolved when the seat types are fetched.
 */
export async function fetchSeatTypesByLayoutId(layoutId) {
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

export const controls = {
	FETCH_SEAT_TYPES_FOR_LAYOUT(action) {
		return fetchSeatTypesByLayoutId(action.layoutId);
	},
};
