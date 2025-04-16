/* global tribe, jQuery, tecTicketsCommerceGatewaySquareCheckout */
window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.commerce = window.tec.tickets.commerce || {};
window.tec.tickets.commerce.square = window.tec.tickets.commerce.square || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
window.tec.tickets.commerce.square.checkout = window.tec.tickets.commerce.square.checkout || {};

( ( $, obj, ky ) => {
	'use strict';
	/**
	 * Checkout Selectors.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		cardElement: '#tec-tc-gateway-square-card-element',
		cardErrors: '#tec-tc-gateway-square-errors',
		paymentMessage: '#tec-tc-gateway-square-payment-message',
		infoForm: '.tribe-tickets__commerce-checkout-purchaser-info-wrapper',
		submitButton: '#tec-tc-gateway-square-checkout-button',
		hiddenElement: '.tribe-common-a11y-hidden',
		purchaserInfoForm: '.tribe-tickets__commerce-checkout-purchaser-info-wrapper__form',
		form: '.tribe-tickets__commerce-checkout-square-form',
	};

	/**
	 * Square Payment Form.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.square = null;

	/**
	 * Square Card instance.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.card = null;

	/**
	 * Loader container.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object|null}
	 */
	obj.checkoutContainer = null;

	/**
	 * Handle displaying errors to the end user
	 *
	 * @since 5.3.0
	 *
	 * @param {Array} errors An array of arrays. Each base array is keyed with the error code and contains a list of error messages.
	 * @param {Function} afterDisplay Callback to run after displaying errors.
	 */
	obj.handleErrorDisplay = ( errors, afterDisplay = () => {} ) => {
		errors.map( e => obj.showNotice( {}, '', e[ 1 ] ) );

		afterDisplay();
	};

	/**
	 * Get the request arguments to setup the calls.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} data The data to send in the request.
	 * @param {Object} headers The headers to send with the request.
	 *
	 * @return {Object} The request arguments.
	 */
	obj.getRequestArgs = ( data, headers ) => {
		if ( 'undefined' === typeof headers ) {
			headers = {
				'X-WP-Nonce': obj.data.nonce
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
	 * Handles payment form errors.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} error Which error we are dealing with.
	 */
	obj.onPaymentError = ( error ) => {
		tribe.tickets.debug.log( 'square', 'paymentError', error );

		const $errors = $( obj.selectors.cardErrors );
		if ( error ) {
			$errors.text( error.message ).show();
		} else {
			$errors.text( '' ).hide();
		}
	};

	/**
	 * Get the verification details for the card.
	 *
	 * @since TBD
	 *
	 * @return {Object} The verification details.
	 */
	obj.getVerificationDetails = () => {
		return {
			intent: 'CHARGE',
			currencyCode: obj.data.currencyCode,
			// billingContract: {
			// 	givenName: null,
			// 	familyName: null,
			// 	email: null,
			// 	phone: null,
			// 	countryCode: null,
			// 	addressLines: null,
			// 	state: null,
			// 	city: null,
			// 	postalCode: null,
			// },
			customerInitiated: true,
			sellerKeyedIn: false,
		};
	};

	/**
	 * Create a payment and handle the response.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} formData The form data from the payment form.
	 */
	obj.createPayment = async ( formData ) => {
		try {
			// Create a payment request with the payment data from the form
			const response = await obj.card.tokenize( obj.getVerificationDetails() );
			if ( response.status === 'OK' ) {
				// Send the payment token to your server for processing
				await obj.processPayment( response.token );
			} else {
				let errorMessage = 'Card tokenization failed.';
				if ( response.errors ) {
					errorMessage = response.errors.map( error => error.message ).join(', ');
				}
				obj.onPaymentError({ message: errorMessage });
				obj.loader.hide();
			}
		} catch ( e ) {
			obj.onPaymentError({ message: e.message || 'An error occurred while processing the payment.' });
			obj.loader.hide();
		}
	};

	/**
	 * Process the payment with our backend.
	 *
	 * @since 5.3.0
	 *
	 * @param {string} sourceId The source ID from Square.
	 */
	obj.processPayment = async ( sourceId ) => {
		// Get form data
		const formData = {};
		const formElements = $( obj.selectors.purchaserInfoForm ).serializeArray();
		$.each( formElements, function( i, element ) {
			formData[ element.name ] = element.value;
		} );

		// Add the payment source ID
		formData.payment_source_id = sourceId;

		try {
			// First create an order via the REST API
			const orderResponse = await ky.post( obj.data.orderEndpoint, obj.getRequestArgs( formData ) ).json();

			if ( ! orderResponse.success ) {
				throw new Error( 'Failed to create order.', { cause: orderResponse } );
			}

			if ( orderResponse.redirect_url ) {
				// If successful, redirect to the success page
				window.location.href = orderResponse.redirect_url;
			}
		} catch ( e ) {
			obj.onPaymentError({ message: e.message || 'An error occurred while processing the payment.' });
			obj.loader.hide();
		}
	};

	/**
	 * Initialize Square Web Payments SDK.
	 *
	 * @since 5.3.0
	 */
	obj.initializeSquare = async () => {
		try {
			if ( ! window.Square ) {
				console.error( 'Square SDK not loaded' );
				return;
			}

			// Initialize Square.js
			const payments = window.Square.payments(obj.data.applicationId, obj.data.locationId);

			// Create a card payment element
			obj.card = await payments.card(obj.data.squareCardOptions);

			// Mount the card element to the DOM
			await obj.card.attach(obj.selectors.cardElement);

			// When the form is submitted
			$(obj.selectors.form).on('submit', (e) => {
				e.preventDefault();

				// Show the loader
				obj.loader.show();

				// Create the payment with form data
				obj.createPayment({});
			});
		} catch (e) {
			console.error('Failed to initialize Square', e);
			obj.onPaymentError({ message: 'Failed to initialize payment form.' });
		}
	};

	/**
	 * Show a notice in the notice area.
	 *
	 * @since 5.3.0
	 *
	 * @param {Object} args The arguments for the notice.
	 * @param {string} type The type of notice.
	 * @param {string} message The message to display.
	 * @param {number} [delay=60000] The delay before the notice auto-dismisses.
	 */
	obj.showNotice = ( args, type, message, delay = 60000 ) => {
		if ( 'function' !== typeof tribe.tickets.commerce.notice.show ) {
			return;
		}

		let noticeArgs = {
			type: type || 'error',
			message: message || '',
			delay: delay,
		};

		noticeArgs = $.extend( noticeArgs, args );

		tribe.tickets.commerce.notice.show( noticeArgs );
	};

	/**
	 * Loader related methods.
	 *
	 * @since 5.3.0
	 */
	obj.loader = {
		// Shows the loading animation.
		show: () => {
			tribe.tickets.loader.show( $( obj.selectors.form ) );

			// Also disable the submit button
			$( obj.selectors.submitButton ).prop( 'disabled', true );
		},

		// Hides the loading animation.
		hide: () => {
			tribe.tickets.loader.hide( $( obj.selectors.form ) );

			// Re-enable the submit button
			$( obj.selectors.submitButton ).prop( 'disabled', false );
		},
	};

	/**
	 * Handles the initialization of the checkout when the page loads.
	 *
	 * @since 5.3.0
	 */
	obj.ready = () => {
		obj.initializeSquare();
	};

	// When the document is ready, initialize the checkout.
	$( obj.ready );

} )( jQuery, window.tec.tickets.commerce.square.checkout, tribe.ky );
