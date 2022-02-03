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

(( $, tc, Stripe, ky ) => {
	'use strict';

	/**
	 * The document element
	 *
	 * @since TBD
	 *
	 * @type {jQuery|HTMLElement}
	 */
	const $document = $( document );

	/**
	 * The gateway.stripe object from the global tribe object
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	const obj = tc.gateway.stripe;

	/**
	 * The billing object from the global tribe object
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	const billing = tc.billing;

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
		cardNumber: '#tec-tc-gateway-stripe-card-number',
		cardExpiry: '#tec-tc-gateway-stripe-card-expiry',
		cardCvc: '#tec-tc-gateway-stripe-card-cvc',
		cardZipWrapper: '#tec-tc-gateway-stripe-card-zip',
		cardElement: '#tec-tc-gateway-stripe-card-element',
		cardErrors: '#tec-tc-gateway-stripe-errors',
		paymentElement: '#tec-tc-gateway-stripe-payment-element',
		paymentMessage: '#tec-tc-gateway-stripe-payment-message',
		submitButton: '#tec-tc-gateway-stripe-checkout-button'
	};

	/**
	 * Handle displaying errors to the end user in the cardErrors field
	 *
	 * @param array errors an array of arrays. Each base array is keyed with the error code and cotains a list of error
	 *     messages.
	 */
	obj.handleErrorDisplay = ( errors ) => {
		var errorEl = document.querySelector( obj.selectors.cardErrors );
		var documentFragment = new DocumentFragment();

		for ( var i = 0; i < errors.length; i++ ) {
			var elp = document.createElement( 'p' );
			var els = document.createElement( 'span' );
			els.innerText = errors[i][0];
			elp.innerText = errors[i][1]
			documentFragment.appendChild( els );
			documentFragment.appendChild( elp );
		}

		errorEl.innerHTML = '';
		errorEl.append( documentFragment );

	}

	/**
	 * Stripe JS library.
	 *
	 * @since TBD
	 *
	 * @type {Object|null}
	 */
	obj.stripeLib = Stripe( obj.checkout.publishableKey );

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
	 * Toggle the submit button enabled/disabled
	 *
	 * @param enable
	 */
	obj.submitButton = ( enable ) => {
		let submitButton = document.querySelector( obj.selectors.submitButton );
		submitButton.disabled = !enable;
	}

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
			return (await obj.handlePaymentSuccess( result ));
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
		console.log( data.error.message );
		tribe.tickets.debug.log( 'stripe', 'handlePaymentError', data );

		return obj.handleErrorDisplay(
			[
				[ data.error.code, data.error.message ]
			]
		);
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
				client_secret: paymentIntent.client_secret
			},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce
			}
		};

		const response = await ky.post( `${obj.checkout.orderEndpoint}/${paymentIntent.id}`, args ).json();

		tribe.tickets.debug.log( 'stripe', 'updateOrder', response );

		return response;
	};

	/**
	 * Submit the payment to stripe code.
	 *
	 * @param {String} order The order object returned from the server.
	 *
	 * @return {Promise<*>}
	 */
	obj.submitMultiPayment = async ( order ) => {
		var elements = obj.stripeElements;
		var billing_details = billing.getDetails( false );
		var order = order;
		return obj.stripeLib.confirmPayment( {
			elements,
			redirect: 'if_required',
			confirmParams: {
				return_url: order.redirect_url,
				payment_method_data: {
					billing_details: billing_details
				}
			}
		} ).then( function( result ) {
			obj.submitButton( true );
			if ( result.error ) {
				obj.handlePaymentError( result );
			} else {
				if ( result.paymentIntent.status === 'succeeded' ) {
					obj.handlePaymentSuccess( result );
				}
			}
		} );
	};

	/**
	 * Submit the Card Element payment to stripe.
	 *
	 * @returns {Promise<void>}
	 */
	obj.submitCardPayment = async () => {
		var billing_details = billing.getDetails( false );

		obj.stripeLib.confirmCardPayment( obj.checkout.paymentIntentData.key, {
			payment_method: {
				card: obj.cardElement,
				billing_details: billing_details
			}
		} ).then( function( result ) {
			obj.submitButton( true );
			if ( result.error ) {
				obj.handlePaymentError( result );
			} else {
				if ( result.paymentIntent.status === 'succeeded' ) {
					obj.handlePaymentSuccess( result );
				}
			}
		} );
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
			json: {
				billing_details: billing.getDetails(),
				payment_intent: obj.checkout.paymentIntentData
			},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce
			}
		};
		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		let response = await ky.post( obj.checkout.orderEndpoint, args ).json();

		tribe.tickets.debug.log( 'stripe', 'createOrder', response );

		return response;
	};

	/**
	 * Starts the process to submit a payment.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handlePayment = async ( event ) => {
		event.preventDefault();

		let order = await obj.handleCreateOrder();
		obj.submitButton( false );

		if ( order.success ) {
			if ( obj.checkout.paymentElement ) {
				obj.submitMultiPayment( order );
			} else {
				obj.submitCardPayment();
			}
		}
	};

	/**
	 * Setup and initialize Stripe API.
	 *
	 * @since TBD
	 *
	 * @return {Promise<void>}
	 */
	obj.setupStripe = async () => {

		if ( obj.checkout.paymentIntentData.errors ) {
			return obj.handleErrorDisplay( obj.checkout.paymentIntentData.errors );
		}

		obj.stripeElements = obj.stripeLib.elements( { clientSecret: obj.checkout.paymentIntentData.key } );

		if ( obj.checkout.paymentElement ) {
			// Instantiate the PaymentElement
			obj.paymentElement = obj.stripeElements.create( 'payment', {
				fields: {
					// We're collecting names and emails separately and sending them in confirmPayment
					// no need to duplicate it here
					name: 'never',
					email: 'never',
					phone: 'auto',
					address: 'auto'
				}
			} );
			obj.paymentElement.mount( obj.selectors.paymentElement );
			return false;
		}

		if ( obj.checkout.cardElementType !== 'compact' ) {
			// Instantiate the CardElement with individual fields
			obj.cardElement = obj.stripeElements.create( 'cardNumber', { showIcon: true, iconStyle: 'default' } );
			obj.cardElement.mount( obj.selectors.cardNumber );
			obj.cardExpiry = obj.stripeElements.create( 'cardExpiry' );
			obj.cardExpiry.mount( obj.selectors.cardExpiry );
			obj.cardCvc = obj.stripeElements.create( 'cardCvc' );
			obj.cardCvc.mount( obj.selectors.cardCvc );
			var cardZipWrapper = document.querySelector( obj.selectors.cardZipWrapper );
			var zipField = document.createElement( 'input' );
			zipField.placeholder = obj.checkout.i18n.zip_code;
			cardZipWrapper.append(zipField);

			return false;
		}

		// Instantiate the CardElement with a single field combo
		obj.cardElement = obj.stripeElements.create( 'card' );
		obj.cardElement.mount( obj.selectors.cardElement );
		obj.cardElement.on( 'change', obj.onCardChange );
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
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
})( jQuery, tribe.tickets.commerce, Stripe, ky );
