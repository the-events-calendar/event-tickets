/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe = tribe.tickets.commerce.gateway.stripe || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe.checkout = {};


( async function ( $, obj, Stripe ) {
	'use strict';
	const $document = $( document );

	// Fetch Publishable API Key
	var response = await fetch( tecTicketsCommerceGatewayStripeCheckout.keyEndpoint ).then(function(response) {
		return response.json();
	});

	var stripe = Stripe( response );
	var elements = stripe.elements();

	var style = {
		base: {
			color: "#32325d",
		}
	};

	var card = elements.create("card", { style: style });
	card.mount("#card-element");


	card.on('change', ({error}) => {
		let displayError = document.getElementById('card-errors');
		if (error) {
			displayError.textContent = error.message;
		} else {
			displayError.textContent = '';
		}
	});
} )( jQuery, tribe.tickets.commerce.gateway.stripe, Stripe );
