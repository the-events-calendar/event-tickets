import { applyFilters } from '@wordpress/hooks';
import { setIsInterruptable } from '@tec/tickets/seating/frontend/session';

/**
 * Checks out a ticket using the Tickets Commerce module.
 * This is the default checkout handler for the Tickets Commerce in the context of the Tickets Seating feature.
 * This method call the backend to get the redirection URL with the cart data.
 *
 * @since 5.16.0
 *
 * @param {FormData} data The data to send to the Tickets Commerce checkout page.
 *
 * @return {Promise<boolean>} A promise that resolves to `true` if the checkout was successful, `false` otherwise.
 */
export async function checkoutWithTicketsCommerce(data) {
	const searchParams = new URLSearchParams(window.location.search);
	searchParams.append('tec-tc-cart', 'redirect');
	const cartUrl = `${window.location.origin}${window.location.pathname}?${searchParams}`;

	// Call the backend to get the redirection URL with the cart data.
	const response = await fetch(cartUrl, {
		method: 'POST',
		body: data,
	});

	if (response.ok && response.url) {
		// We're going to leave the page: this should not interrupt the timer and clear the session.
		setIsInterruptable(false);

		// We got a Checkout page URL back: redirect to it.
		window.location.href = response.url;

		// This return value might never be used, due to the previous redirection, but it's here to make the linter happy.
		return true;
	}

	return false;
}

/**
 * Returns the checkout handler for a given provider.
 * This function filters the checkout handler for a given provider in the context of the Tickets Seating feature.
 *
 * @since 5.16.0
 *
 * @param {string} provider The provider to get the checkout handler for.
 *
 * @return {Function|null} The checkout handler for the provider, or `null` if none is found.
 */
export function getCheckoutHandlerForProvider(provider) {
	let checkoutHandler;

	switch (provider) {
		case 'TECTicketsCommerceModule':
		case 'TEC\\Tickets\\Commerce\\Module':
			checkoutHandler = checkoutWithTicketsCommerce;
			break;
		default:
			checkoutHandler = null;
			break;
	}

	/**
	 * Filters the checkout handler for a given provider in the context of the Tickets Seating feature..
	 *
	 * @since 5.16.0
	 *
	 * @param {Function|null} checkoutHandler The checkout handler for the provider.
	 * @param {string}        provider        The provider to get the checkout handler for.
	 */
	checkoutHandler = applyFilters(
		'tec.tickets.seating.checkoutHandler',
		checkoutHandler,
		provider
	);

	return checkoutHandler;
}
