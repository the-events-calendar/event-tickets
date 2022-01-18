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

(async function( $, obj, Stripe ) {
	'use strict';
	const $document = $( document );

	obj.checkout = tecTicketsCommerceGatewayStripeCheckout;

	// Fetch Publishable API Key and Initialize Stripe Elements on Ready
	var response = await fetch( obj.checkout.keyEndpoint, {
		method: 'POST',
		headers: {
			'Accept': 'application/json',
			'Content-Type': 'application/json'
		},
		body: JSON.stringify( {nonce: obj.checkout.keyNonce } )
	} ).then( function( response ) {
		return response.json();
	} );

	var stripe = Stripe( response );
	obj.stripeElements = stripe.elements();

	/**
	 * Create an order
	 */
	obj.createOrder = async function() {

		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		var response = await fetch( obj.checkout.orderEndpoint, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( {nonce: obj.checkout.keyNonce } )
		} ).then( function( response ) {
			return response.json();
		} );

		console.log('create order');
	};

	/**
	 * Starts the process to submit a payment
	 *
	 * @param event
	 */
	obj.submitPayment = function( event ) {
		event.preventDefault();

		obj.createOrder();
	};

	/**
	 * Checkout Selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		button: 'tec-tc-gateway-stripe-checkout-button',
	};

	/**
	 * Event callbacks
	 * @type {{submit: tribe.tickets.commerce.gateway.stripe.submitPayment}}
	 */
	obj.callbacks = {
		submit: obj.submitPayment,
	}

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = function() {

		// Load CardElement
		window.onload = ( event ) => {
			var style = {
				base: {
					color: "#32325d"
				}
			};

			var card = obj.stripeElements.create( "card", { style: style } );
			card.mount( "#card-element" );

			card.on( 'change', ( { error } ) => {
				let displayError = document.getElementById( 'card-errors' );
				if ( error ) {
					displayError.textContent = error.message;
				} else {
					displayError.textContent = '';
				}
			} );
		};

		// Handle submit
		var paymentButton = document.getElementById( obj.selectors.button );
		paymentButton.addEventListener( 'click', obj.callbacks.submit )
	};

	obj.bindEvents();

})( jQuery, tribe.tickets.commerce.gateway.stripe, Stripe );
