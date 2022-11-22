/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout */

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe = tribe.tickets.commerce.gateway.stripe || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe.checkout = {};

( ( $, obj, Stripe, ky ) => {
	'use strict';

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.checkout = tecTicketsCommerceGatewayStripeCheckout;

	/**
	 * Checkout Selectors.
	 *
	 * @since 5.3.0
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
		submitButton: '#tec-tc-gateway-stripe-checkout-button',
		hiddenElement: '.tribe-common-a11y-hidden'
	};

	/**
	 * Stripe JS library.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.stripeLib = Stripe( obj.checkout.publishableKey );

	/**
	 * Stripe Elements API instance.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.stripeElements = null;

	/**
	 * Loader container.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.checkoutContainer = null;

	/**
	 * Handle displaying errors to the end user in the cardErrors field
	 *
	 * @param array errors an array of arrays. Each base array is keyed with the error code and cotains a list of error
	 *     messages.
	 */
	obj.handleErrorDisplay = ( errors ) => {
		errors.map( e => obj.showNotice( {}, '', e[ 1 ] ) );
	};

	/**
	 * Get the request arguments to setup the calls.
	 *
	 * @since 5.3.0
	 *
	 * @param data
	 * @param headers
	 *
	 * @return {{headers: {"X-WP-Nonce"}, throwHttpErrors: boolean, json, hooks: {beforeError: (function(*): *)[]}}}
	 */
	obj.getRequestArgs = ( data, headers ) => {
		if ( 'undefined' === typeof headers ) {
			headers = {
				'X-WP-Nonce': obj.checkout.nonce
			};
		}

		const args = {
			headers: headers,
			hooks: {
				beforeRetry: [
					obj.onBeforeRetry
				],
				beforeError: [
					obj.onBeforeError
				]
			},
			timeout: 30000,
			throwHttpErrors: false
		};

		if ( data ) {
			args.json = data;
		}

		return args;
	};

	/**
	 * Preventing errors to be thrown when using Ky
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} error
	 *
	 * @return {*}
	 */
	obj.onBeforeRetry = async ( error ) => {
		console.log( error );

		return ky.stop;
	};

	/**
	 * Preventing errors to be thrown when using Ky
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} error
	 *
	 * @return {*}
	 */
	obj.onBeforeError = async ( error ) => {
		console.log( error );

		return ky.stop;
	};

	/**
	 * Builds the wallets object to use when creating a Payment Element
	 *
	 * @returns {{apple_pay: string, google_pay: string}}
	 */
	obj.getWallets = () => {
		const settings = {
			apple_pay: 'never',
			google_pay: 'never',
		};

		if ( ! obj.checkout.wallet_settings ) {
			return settings;
		}

		const wallet = obj.checkout.wallet_settings;

		if ( wallet.apple_pay === true ) {
			settings.apple_pay = 'auto';
		}

		if ( wallet.google_pay === true ) {
			settings.google_pay = 'auto';
		}

		return settings;
	};

	/**
	 * Handles the changing of the card field.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} error Which error we are dealing with.
	 */
	obj.onCardChange = ( {error} ) => {
		tribe.tickets.debug.log( 'stripe', 'cardChange', error );
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
		$( obj.selectors.submitButton ).prop( 'disabled', ! enable );
	};

	/**
	 * Receive the Payment from Stripe.
	 *
	 * @since 5.3.0
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
			return ( obj.handlePaymentSuccess( result ) );
		}
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {boolean}
	 */
	obj.handlePaymentError = ( data ) => {
		$( obj.selectors.cardErrors ).val( data.error.message );
		tribe.tickets.debug.log( 'stripe', 'handlePaymentError', data );

		tribe.tickets.loader.hide( obj.checkoutContainer );

		return obj.handleErrorDisplay(
			[
				[ data.error.code, data.error.message ]
			]
		);
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since 5.3.0
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
	 * Handle payments in cases other than an automatic confirmation
	 *
	 * @param data
	 *
	 * @returns {Promise<boolean>}
	 */
	obj.handlePaymentDelayed = async ( data ) => {
		tribe.tickets.debug.log( 'stripe', 'handlePaymentDelayed', data );

		const response = await obj.handleUpdateOrder( data.paymentIntent );

		// Redirect the user to the success page.
		window.location.replace( response.redirect_url );

		return true;
	};

	/**
	 * Updates the Order based on a paymentIntent from Stripe.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} paymentIntent Payment intent Object from Stripe.
	 *
	 * @return {Promise<*>}
	 */
	obj.handleUpdateOrder = async ( paymentIntent ) => {
		const args = obj.getRequestArgs( {
			client_secret: paymentIntent.client_secret
		} );
		let response;

		try {
			response = await ky.post( `${obj.checkout.orderEndpoint}/${paymentIntent.id}`, args ).json();
		} catch( error ) {
			response = error;
		}

		tribe.tickets.debug.log( 'stripe', 'updateOrder', response );

		return response;
	};

	/**
	 * Submit the payment to Stripe for Payment Element.
	 *
	 * @since 5.3.0
	 *
	 * @param {String} order The order object returned from the server.
	 *
	 * @return {Promise<*>}
	 */
	obj.submitMultiPayment = async ( order ) => {

		return obj.stripeLib.confirmPayment( {
			elements: obj.stripeElements,
			redirect: 'if_required',
			confirmParams: {
				return_url: order.redirect_url
			}
		} ).then( obj.handleConfirmPayment );
	};

	/**
	 * Handle the confirmation of the Payment on PaymentElement.
	 *
	 * @since 5.3.0
	 *
	 * @param result
	 */
	obj.handleConfirmPayment = ( result ) => {
		obj.submitButton( true );
		if ( result.error ) {
			return obj.handlePaymentError( result );
		} else {

			if ( result.paymentIntent.status === 'succeeded' ) {
				return obj.handlePaymentSuccess( result );
			}

			return obj.handlePaymentDelayed( result );
		}
	};

	/**
	 * Submit the Card Element payment to Stripe.
	 *
	 * @since 5.3.0
	 *
	 * @returns {Promise<*>}
	 */
	obj.submitCardPayment = async () => {

		return obj.stripeLib.confirmCardPayment( obj.checkout.paymentIntentData.key, {
			payment_method: {
				card: obj.cardElement,
			}
		} ).then( obj.handleConfirmCardPayment );
	};

	/**
	 * Handle the confirmation of the Payment on CardElement.
	 *
	 * @since 5.3.0
	 *
	 * @param result
	 */
	obj.handleConfirmCardPayment = ( result ) => {
		obj.submitButton( true );
		if ( result.error ) {
			obj.handlePaymentError( result );
		} else {
			if ( result.paymentIntent.status === 'succeeded' ) {
				return obj.handlePaymentSuccess( result );
			}

			return obj.handlePaymentDelayed( result );
		}
	};

	/**
	 * Create an order and start the payment process.
	 *
	 * @since 5.3.0
	 *
	 * @return {Promise<*>}
	 */
	obj.handleCreateOrder = async () => {
		const args = obj.getRequestArgs( {
			purchaser: obj.getPurchaserData(),
			payment_intent: obj.checkout.paymentIntentData
		} );
		let response;

		try {
			// Fetch Publishable API Key and Initialize Stripe Elements on Ready
			response = await ky.post( obj.checkout.orderEndpoint, args ).json();
		} catch( error ) {
			response = error;
		}

		tribe.tickets.debug.log( 'stripe', 'createOrder', response );

		return response;
	};

	/**
	 * Starts the process to submit a payment.
	 *
	 * @since 5.3.0
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handlePayment = async ( event ) => {
		event.preventDefault();

		obj.checkoutContainer = $( event.target ).closest( tribe.tickets.commerce.selectors.checkoutContainer );

		obj.hideNotice( obj.checkoutContainer );

		tribe.tickets.loader.show( obj.checkoutContainer );

		let order = await obj.handleCreateOrder();
		obj.submitButton( false );

		if ( order.success ) {
			if ( obj.checkout.paymentElement ) {
				obj.submitMultiPayment( order );
			} else {
				obj.submitCardPayment();
			}
		} else {
			tribe.tickets.loader.hide( obj.checkoutContainer );
			obj.showNotice( {}, order.message, '' );
		}

		obj.submitButton( true );
	};

	/**
	 * Configure the CardElement with separate fields.
	 *
	 * @link https://stripe.com/docs/js/elements_object/create_element?type=cardNumber#elements_create-options
	 *
	 * @since 5.3.0
	 */
	obj.setupSeparateCardElement = () => {
		// Instantiate the CardElement with individual fields.
		obj.cardElement = obj.stripeElements.create( 'cardNumber', {
			showIcon: true,
			iconStyle: 'default',
			style: obj.checkout.cardElementStyle,
		} );
		obj.cardElement.mount( obj.selectors.cardNumber );
		obj.cardElement.on( 'change', obj.onCardChange );

		obj.cardExpiry = obj.stripeElements.create( 'cardExpiry', {
			style: obj.checkout.cardElementStyle,
		} );
		obj.cardExpiry.mount( obj.selectors.cardExpiry );
		obj.cardExpiry.on( 'change', obj.onCardChange );

		obj.cardCvc = obj.stripeElements.create( 'cardCvc', {
			style: obj.checkout.cardElementStyle,
		} );
		obj.cardCvc.mount( obj.selectors.cardCvc );
		obj.cardCvc.on( 'change', obj.onCardChange );
	};

	/**
	 * Configure the CardElement with compact fields.
	 *
	 * @link https://stripe.com/docs/js/elements_object/create_element?type=card#elements_create-options
	 *
	 * @since 5.3.0
	 */
	obj.setupCompactCardElement = () => {
		// Instantiate the CardElement with a single field combo.
		obj.cardElement = obj.stripeElements.create( 'card', {
			style: obj.checkout.cardElementStyle,
		} );
		obj.cardElement.mount( obj.selectors.cardElement );
		obj.cardElement.on( 'change', obj.onCardChange );
	};

	/**
	 * Configure the PaymentElement with separate fields.
	 *
	 * @link https://stripe.com/docs/js/element/payment_element
	 *
	 * @since 5.3.0
	 */
	obj.setupPaymentElement = () => {
		const wallets = obj.getWallets();
		// Instantiate the PaymentElement
		obj.paymentElement = obj.stripeElements.create( 'payment', {
			fields: {
				name: 'auto',
				email: 'auto',
				phone: 'auto',
				address: 'auto'
			},
			wallets
		} );
		obj.paymentElement.mount( obj.selectors.paymentElement );
	};

	/**
	 * Setup and initialize Stripe API.
	 *
	 * @since 5.3.0
	 *
	 * @return {Promise<void>}
	 */
	obj.setupStripe = async () => {

		if ( 0 === obj.checkout.paymentIntentData.length ) {
			return;
		}

		if ( obj.checkout.paymentIntentData.errors ) {
			obj.submitButton( false );
			$( obj.selectors.submitButton ).addClass( obj.selectors.hiddenElement.className() );
			return obj.handleErrorDisplay( obj.checkout.paymentIntentData.errors );
		}

		obj.stripeElements = obj.stripeLib.elements( {
			clientSecret: obj.checkout.paymentIntentData.key,
			appearance: obj.checkout.elementsAppearance,
		} );

		if ( obj.checkout.paymentElement ) {
			obj.setupPaymentElement();
			return;
		}

		if ( 'separate' === obj.checkout.cardElementType ) {
			obj.setupSeparateCardElement();
		} else if ( 'compact' === obj.checkout.cardElementType ) {
			obj.setupCompactCardElement();
		}
	};

	/**
	 * Get purchaser form data.
	 *
	 * @since 5.3.0
	 *
	 * @return {Object}
	 */
	obj.getPurchaserData = () => tribe.tickets.commerce.getPurchaserData( $( tribe.tickets.commerce.selectors.purchaserFormContainer ) );

	/**
	 * Shows the notice for the checkout container for Stripe.
	 *
	 * @since 5.3.0
	 *
	 * @param {jQuery} $container Parent container of notice element.
	 * @param {string} title Notice Title.
	 * @param {string} content Notice message content.
	 */
	obj.showNotice = ( $container, title, content ) => {
		if ( ! $container || ! $container.length ) {
			$container = $( tribe.tickets.commerce.selectors.checkoutContainer );
		}
		const notice = tribe.tickets.commerce.notice;
		const $item = $container.find( notice.selectors.item );
		notice.populate( $item, title, content );
		notice.show( $item );
	};

	/**
	 * Hides the notice for the checkout container for Stripe.
	 *
	 * @since 5.3.0
	 *
	 * @param {jQuery} $container Parent container of notice element.
	 */
	obj.hideNotice = ( $container ) => {
		if ( ! $container.length ) {
			$container = $( tribe.tickets.commerce.selectors.checkoutContainer );
		}

		const notice = tribe.tickets.commerce.notice;
		const $item = $container.find( notice.selectors.item );
		notice.hide( $item );
	};
	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since 5.3.0
	 */
	obj.bindEvents = () => {
		$( obj.selectors.submitButton ).on( 'click', obj.handlePayment );
	};

	/**
	 * When the page is ready.
	 *
	 * @since 5.3.0
	 */
	obj.ready = () => {
		obj.setupStripe();
		obj.bindEvents();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.gateway.stripe, Stripe, tribe.ky );
