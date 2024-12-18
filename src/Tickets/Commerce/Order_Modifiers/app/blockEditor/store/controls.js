import {
	nonce,
	baseUrl
} from '@tec/tickets/order-modifiers/rest';

/**
 * Fetches fees from the API.
 *
 * @since 5.18.0
 *
 * @return {Promise<{feesAutomatic: (*|*[]), feesAvailable: (*|*[])}>}
 */
export async function fetchFeesFromAPI() {
	const url = new URL( `${ baseUrl }/fees` );
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

	const feesAutomatic = 'object' === typeof json?.automatic_fees ? Object.values( json.automatic_fees ) : [];
	const feesAvailable = 'object' === typeof json?.selectable_fees ? Object.values( json.selectable_fees ) : [];

	// Ensure the ids are integers.
	feesAutomatic.forEach( ( fee ) => fee.id = parseInt( fee.id ) );
	feesAvailable.forEach( ( fee ) => fee.id = parseInt( fee.id ) );

	return {
		feesAutomatic: feesAutomatic,
		feesAvailable: feesAvailable,
	};
}

export const controls = {
	FETCH_FEES_FROM_API() {
		return fetchFeesFromAPI();
	},
}
