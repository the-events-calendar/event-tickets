/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout */

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

( ( $, obj, Stripe, ky ) => {
	'use strict';
	const $document = $( document );

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.checkout = tecTicketsCommerceGatewayStripeCheckout;

	/**
	 * Checkout Selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		cardElement: '#tec-tc-gateway-stripe-card-element',
		cardErrors: '#tec-tc-gateway-stripe-card-errors',
		submitButton: '#tec-tc-gateway-stripe-checkout-button',
	};

	/**
	 * Stripe JS library.
	 *
	 * @since TBD
	 *
	 * @type {Object|null}
	 */
	obj.stripeLib = Stripe( obj.checkout.publishableKey );

	/**
	 * Stripe JS library elements.
	 *
	 * @since TBD
	 *
	 * @type {Object|null}
	 */
	obj.stripeElements = obj.stripeLib.elements();

	/**
	 * Settings for the Card Element from Stripe.
	 *
	 * @since TBD
	 *
	 * @return {{style: {base: {color: string}}}}
	 */
	obj.getCardOptions = () => {
		return {
			style: {
				base: {
					color: "#32325d"
				}
			}
		};
	};

	/**
	 * Setup and initialize Stripe API.
	 *
	 * @since TBD
	 *
	 * @return {Promise<void>}
	 */
	obj.setupStripe = async () => {
		obj.stripeCard = obj.stripeElements.create( 'card', obj.getCardOptions() );
		obj.stripeCard.mount( obj.selectors.cardElement );
		obj.stripeCard.on( 'change', obj.onCardChange );
	};

	/**
	 * Handles the changing of the card field.
	 *
	 * @since TBD
	 *
	 * @param {Object} error Which error we are dealing with.
	 */
	obj.onCardChange = ( { error } ) => {
		let displayError = $( obj.selectors.cardErrors );
		if ( error ) {
			displayError.text( error.message );
		} else {
			displayError.text( '' );
		}
	};

	/**
	 * Receive the Payment from Stripe.
	 *
	 * @since TBD
	 *
	 * @param {Object} result Result from the payment request.
	 *
	 * @return {boolean}
	 */
	obj.handleReceivePayment = async ( result ) => {
		tribe.tickets.debug.log( 'stripe', 'handleReceivePayment', result );
		if ( result.error ) {
			return obj.handlePaymentError( result );
		}

		if ( 'succeeded' === result.paymentIntent.status ) {
			return ( await obj.handlePaymentSuccess( result ) );
		}
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {boolean}
	 */
	obj.handlePaymentError = ( data ) => {
		tribe.tickets.debug.log( 'stripe', 'handlePaymentError', data );
		return false;
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {boolean}
	 */
	obj.handlePaymentSuccess = async ( data ) => {
		tribe.tickets.debug.log( 'stripe', 'handlePaymentSuccess', data );

		const response = await obj.handleUpdateOrder( data.paymentIntent );

		// Redirect the user to the success page.
		window.location.replace( response.redirect_url );
		return true;
	};

	/**
	 * Updates the Order based on a paymentIntent from Stripe.
	 *
	 * @since TBD
	 *
	 * @param {Object} paymentIntent Payment intent Object from Stripe.
	 *
	 * @return {Promise<*>}
	 */
	obj.handleUpdateOrder = async ( paymentIntent ) => {
		const args = {
			json: {
				client_secret: paymentIntent.client_secret,
			},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce,
			}
		};

		const response = await ky.post( `${obj.checkout.orderEndpoint}/${paymentIntent.id}`, args ).json();

		tribe.tickets.debug.log( 'stripe', 'updateOrder', response );

		return response;
	};

	/**
	 * Submit the payment to stripe code.
	 *
	 * @param {String} secret Which secret we need to use to confirm the Payment.
	 *
	 * @return {Promise<*>}
	 */
	obj.submitPayment = async ( secret ) => {
		return obj.stripeLib.confirmCardPayment( secret, {
			payment_method: {
				card: obj.stripeCard,
				billing_details: {
					name: 'user name', // @todo get this value
				},
			}
		} ).then( obj.handleReceivePayment );
	};

	/**
	 * Create an order and start the payment process.
	 *
	 * @since TBD
	 *
	 * @return {Promise<*>}
	 */
	obj.handleCreateOrder = async () => {
		const args = {
			json: {},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce,
			}
		};
		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		let response = await ky.post( obj.checkout.orderEndpoint, args ).json();

		tribe.tickets.debug.log( 'stripe', 'createOrder', response );

		if ( true === response.success ) {
			return await obj.submitPayment( response.client_secret );
		}
	};

	/**
	 * Starts the process to submit a payment.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handlePayment = ( event ) => {
		event.preventDefault();
		obj.handleCreateOrder();
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		// Handle submit
		$( obj.selectors.submitButton ).on( 'click', obj.handlePayment );
	};

	/**
	 * When the page is ready.
	 *
	 * @since TBD
	 */
	obj.ready = () => {
		obj.setupStripe();
		obj.bindEvents();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.gateway.stripe, Stripe, tribe.ky );