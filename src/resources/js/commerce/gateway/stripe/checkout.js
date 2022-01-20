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

(( $, obj, Stripe ) => {
	'use strict';
	const $document = $( document );

	obj.checkout = tecTicketsCommerceGatewayStripeCheckout;

	/**
	 * Checkout Selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		cardElementDiv: 'tec-tc-gateway-stripe-card-element',
		cardErrorsDiv: 'tec-tc-gateway-stripe-card-errors',
		submitButton: 'tec-tc-gateway-stripe-checkout-button',
	};

	/**
	 * Event callbacks
	 * @type {{submit: tribe.tickets.commerce.gateway.stripe.submitPayment}}
	 */
	obj.callbacks = {
		submit: obj.handlePayment
	};

	obj.setupStripe = async () => {
		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		let response = await fetch( obj.checkout.keyEndpoint, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				body: JSON.stringify( { nonce: obj.checkout.keyNonce } )
			} )
			.then( response => response.json() );

		obj.stripeLib = Stripe( response );
		obj.stripeElements = obj.stripeLib.elements();
	};

	obj.submitPayment = async ( secret ) => {

		obj.stripeLib.confirmCardPayment( secret, {
			payment_method: {
				card: obj.checkout.card,
				billing_details: {
					name: 'user name' // @todo get this value
				}
			}
		} ).then( ( result ) => {
			console.log( result );
			if ( result.error ) {
				console.log( result.error.message );
				return false;
			}

			if ( result.paymentIntent.status === 'succeeded' ) {
				console.log( 'great success!' );
			}
		} );

	};

	/**
	 * Create an order
	 *
	 * @since TBD
	 */
	obj.createOrder = async () => {

		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		let response = await fetch( obj.checkout.orderEndpoint, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( { nonce: obj.checkout.orderNonce } )
		} ).then( response => response.json() );

		if ( true === response.success ) {
			obj.submitPayment( response.client_secret );
		}

		console.log( response );
	};

	/**
	 * Starts the process to submit a payment
	 *
	 * @since TBD
	 *
	 * @param event
	 */
	obj.handlePayment = ( event ) => {
		event.preventDefault();
		obj.createOrder();
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		// Handle submit
		$( obj.selectors.submitButton ).on( 'click', obj.callbacks.submit );
	};

	obj.ready = () => {
		obj.setupStripe();

		// Initialize
		obj.bindEvents();
	};

	obj.onCardChange = ( { error } ) => {
		let displayError = document.getElementById( obj.selectors.cardErrorsDiv );
		if ( error ) {
			displayError.textContent = error.message;
		} else {
			displayError.textContent = '';
		}
	};

	obj.onLoad =  ( event ) => {
		const style = {
			base: {
				color: "#32325d"
			}
		};

		obj.checkout.card = obj.stripeElements.create( 'card', { style: style } );
		obj.checkout.card.mount( document.getElementById( obj.selectors.cardElementDiv ) );
		obj.checkout.card.on( 'change', obj.onCardChange );
	};

	// Bind the onload of the page.
	$( window ).load( obj.onLoad );

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.gateway.stripe, Stripe );
