window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.ajax = window.tec.tickets.seating.ajax || {};
window.tec.tickets.seating.ajax.urls = window.tec.tickets.seating.ajax.urls || {};

const {seatTypesByLayoutId} = window.tec.tickets.seating.ajax.urls;

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
	const response = await fetch(
		`${seatTypesByLayoutId}&layout=${layoutId}`,
		{
			method: 'GET',
			headers: {
				'Accept': 'application/json',
			},
		},
	);

	if (response.status !== 200) {
		throw new Error(
			`Failed to fetch seat types for layout ID ${layoutId}. Status: ${response.status}`,
		);
	}

	const json = await response.json();

	return json?.data || [];
}

window.tec.tickets.seating.ajax = {
	...window.tec.tickets.seating.ajax,
	fetchSeatTypesByLayoutId,
};