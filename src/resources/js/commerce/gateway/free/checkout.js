/* global tribe, jQuery, tecTicketsCommerceGatewayFreeCheckout */

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.10.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.free = tribe.tickets.commerce.gateway.free || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.10.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.free.checkout = {};

( ( $, obj, ky ) => {
	'use strict';

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since 5.10.0
	 *
	 * @type {Object}
	 */
	obj.checkout = tecTicketsCommerceGatewayFreeCheckout;

	/**
	 * Checkout Selectors.
	 *
	 * @since 5.10.0
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		submitButton: '#tec-tc-gateway-free-checkout-button',
		hiddenElement: '.tribe-common-a11y-hidden'
	};

	/**
	 * Loader container.
	 *
	 * @since 5.10.0
	 *
	 * @type {Object|null}
	 */
	obj.checkoutContainer = null;

	/**
	 * Preventing errors to be thrown when using Ky
	 *
	 * @since 5.10.0
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
	 * @since 5.10.0
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
	 * Get the request arguments to setup the calls.
	 *
	 * @since 5.10.0
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
	 * Hides the notice for the checkout container.
	 *
	 * @since 5.10.0
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
	 * Shows the notice for the checkout container.
	 *
	 * @since 5.10.0
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
	 * Toggle the submit button enabled/disabled
	 *
	 * @param enable
	 */
	obj.submitButton = ( enable ) => {
		$( obj.selectors.submitButton ).prop( 'disabled', ! enable );
	};

	/**
	 * Starts the process to submit a payment.
	 *
	 * @since 5.10.0
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
			window.location.replace( order.redirect_url );
		} else {
			tribe.tickets.loader.hide( obj.checkoutContainer );
			obj.showNotice( {}, order.message, '' );
		}

		obj.submitButton( true );
	};

	/**
	 * Create an order and start the payment process.
	 *
	 * @since 5.10.0
	 *
	 * @return {Promise<*>}
	 */
	obj.handleCreateOrder = async () => {
		const args = obj.getRequestArgs( {
			purchaser: obj.getPurchaserData(),
		} );
		let response;

		try {
			response = await tribe.ky.post( obj.checkout.orderEndpoint, args ).json();
		} catch( error ) {
			response = error;
		}

		tribe.tickets.debug.log( 'free', 'createOrder', response );

		return response;
	};

	/**
	 * Get purchaser form data.
	 *
	 * @since 5.10.0
	 *
	 * @return {Object}
	 */
	obj.getPurchaserData = () => tribe.tickets.commerce.getPurchaserData( $( tribe.tickets.commerce.selectors.purchaserFormContainer ) );

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since 5.10.0
	 */
	obj.bindEvents = () => {
		$( obj.selectors.submitButton ).on( 'click', obj.handlePayment );
	};

	/**
	 * When the page is ready.
	 *
	 * @since 5.10.0
	 */
	obj.ready = () => {
		obj.bindEvents();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.gateway.free, tribe.ky );
