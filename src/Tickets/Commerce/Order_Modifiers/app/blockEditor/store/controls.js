// @todo: set separate file to manage these variables.
const restUrl = 'https://stellarwp.test/wp-json/tribe/tickets/v1/fees/';
const restNonce = '';

import {
	nonce,
	baseUrl
} from '@tec/tickets/order-modifiers/rest';

export async function fetchFeesFromAPI() {
	const url = new URL( `${baseUrl}/fees` );
	url.searchParams.set( '_wpnonce', nonce );

	const response = await fetch( url.toString(), {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
	} );

	if ( response.status !== 200 ) {
		throw new Error(
			`Failed to fetch fees from API. Status: ${ response.status }`
		);
	}

	const json = await response.json();

	return {
		feesAutomatic: json?.data?.automatic_fees || [],
		feesSelectable: json?.data?.selectable_fees || [],
	};
}

export const controls = {
	FETCH_FEES_FROM_API() {
		return fetchFeesFromAPI();
	},
}
