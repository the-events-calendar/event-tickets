/* global tribe, jQuery, paypal, tecTicketsCommerceGatewayPayPalCheckout */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.paypal = tribe.tickets.commerce.gateway.paypal || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.paypal.checkout = {};

/**
 * Initializes in a Strict env the code that manages the checkout for PayPal.
 *
 * @since 5.1.9
 *
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.commerce.gateway.paypal.checkout
 *
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * PayPal Order handling endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @type {string}
	 */
	obj.orderEndpointUrl = tecTicketsCommerceGatewayPayPalCheckout.orderEndpoint;

	/**
	 * Set of timeout IDs so we can clear when the process of purchasing starts.
	 *
	 * @since 5.1.9
	 *
	 * @type {Array}
	 */
	obj.timeouts = [];

	/**
	 * PayPal Checkout Selectors.
	 *
	 * @since 5.1.9
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		checkoutScript: '.tec-tc-gateway-paypal-checkout-script',
		activePayment: '.tec-tc-gateway-paypal-payment-active',
		buttons: '#tec-tc-gateway-paypal-checkout-buttons',
	};

	/**
	 * Handles the creation of the orders via PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data PayPal data passed to this method.
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.handleCancel = function ( data, $container ) {
		console.log( 'handleCancel', arguments );
		$container.removeClass( obj.selectors.activePayment.className() );
	};

	/**
	 * Handles the creation of the orders via PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} error PayPal data passed to this method.
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.handleGenericError = function ( error, $container ) {
		console.log( 'handleGenericError', arguments );
		$container.removeClass( obj.selectors.activePayment.className() );
	};

	/**
	 * Handles the click when one of the buttons were clicked.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.handleClick = function ( $container ) {
		$container.addClass( obj.selectors.activePayment.className() );
	};

	/**
	 * Handles the creation of the orders via PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data PayPal data passed to this method.
	 * @param {Object} actions PayPal actions available on order creation.
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrder = function ( data, actions, $container ) {
		console.log( 'handleCreateOrder', arguments );
		return fetch(
			obj.orderEndpointUrl,
			{
				method: 'POST',
				headers: {
					'X-WP-Nonce': $container.find( tribe.tickets.commerce.selectors.nonce ).val(),
				}
			}
		)
			.then( response => response.json() )
			.then( data => {
				console.log( data );
				if ( data.success ) {
					return obj.handleCreateOrderSuccess( data );
				} else {
					return obj.handleCreateOrderFail( data );
				}
			} )
			.catch( obj.handleCreateOrderError );
	};

	/**
	 * When a successful request is completed to our Create Order endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {string}
	 */
	obj.handleCreateOrderSuccess = function ( data ) {
		console.log( 'handleCreateOrderSuccess', arguments );
		return data.id;
	};

	/**
	 * When a failed request is completed to our Create Order endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrderFail = function ( data ) {
		console.log( 'handleCreateOrderFail', arguments );
		tribe.tickets.commerce.showNotice( data );
	};

	/**
	 * When a error happens on the fetch request to our Create Order endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} error Which error the fetch() threw on requesting our endpoints.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrderError = function ( error ) {
		console.log( 'handleCreateOrderError', arguments );
	};

	/**
	 * Handles the Approval of the orders via PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data PayPal data passed to this method.
	 * @param {Object} actions PayPal actions available on approve.
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.handleApprove = function ( data, actions, $container ) {
		console.log( 'handleApprove', arguments );
		/**
		 * @todo On approval we receive a bit more than just the orderID on the data object
		 *       we should be passing those to the BE.
		 */
		return fetch(
			obj.orderEndpointUrl + '/' + data.orderID,
			{
				method: 'POST',
				headers: {
					'X-WP-Nonce': $container.find( tribe.tickets.commerce.selectors.nonce ).val(),
				}
			}
		)
			.then( response => response.json() )
			.then( data => {
				console.log( data );
				if ( data.success ) {
					return obj.handleApproveSuccess( data );
				} else {
					return obj.handleApproveFail( data );
				}
			} )
			.catch( obj.handleApproveError );
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleApproveSuccess = function ( data ) {
		console.log( 'handleApproveSuccess', arguments );
		// When this Token has expired we just refresh the browser.
		window.location.replace( data.redirect_url );
	};

	/**
	 * When a failed request is completed to our Approval endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleApproveFail = function ( data ) {
		console.log( 'handleApproveFail', arguments );
		tribe.tickets.commerce.showNotice( data );
	};

	/**
	 * When a error happens on the fetch request to our Approval endpoint.
	 *
	 * @since 5.1.9
	 *
	 * @param {Object} error Which error the fetch() threw on requesting our endpoints.
	 *
	 * @return {void}
	 */
	obj.handleApproveError = function ( error ) {
		console.log( 'handleApproveError', arguments );

	};

	/**
	 * Unbinds the description toggle.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.getButtonConfig = function ( $container ) {
		let configs = {
			style: {
				layout: 'vertical',
				color: 'blue',
				shape: 'rect',
				label: 'paypal'
			},
			createOrder: ( data, actions ) => { return obj.handleCreateOrder( data, actions, $container ); },
			onApprove: ( data, actions ) => { return obj.handleApprove( data, actions, $container ); },
			onCancel: ( data ) => { return obj.handleCancel( data, $container ); },
			onError: ( data ) => { return obj.handleGenericError( data, $container ); },
			onClick: () => { return obj.handleClick( $container ); }
		};

		return configs;
	};

	/**
	 * Redirect the user back to the checkout page when the Token is expired so it gets refreshed properly.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery Object.
	 */
	obj.timeoutRedirect = ( $container ) => {
		// Prevent redirecting when a payment is engaged.
		if ( $container.is( obj.selectors.activePayment.className() ) ) {
			return;
		}

		// When this Token has expired we just refresh the browser.
		window.location.replace( window.location.href );
	};

	/**
	 * Setup the Buttons for PayPal Checkout.
	 *
	 * @since 5.1.9
	 *
	 * @param  {Event}   event      event object for 'afterSetup.tecTicketsCommerce' event
	 * @param  {jQuery}  $container jQuery object of checkout container.
	 *
	 * @return {void}
	 */
	obj.setupButtons = function ( event, $container ) {
		paypal.Buttons( obj.getButtonConfig( $container ) ).render( obj.selectors.buttons );

		const $checkoutScript = $container.find( obj.selectors.checkoutScript );

		if ( $checkoutScript.length && $checkoutScript.is( '[data-client-token-expires-in]' ) ) {
			const timeout = parseInt( $checkoutScript.data( 'clientTokenExpiresIn' ), 10 ) * 1000;
			obj.timeouts.push( setTimeout( obj.timeoutRedirect, timeout, $container ) );
		}
	};

	/**
	 * Setup the triggers for Ticket Commerce loader view.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.setupLoader = function() {
		$document.trigger( tribe.tickets.commerce.customEvents.showLoader );

		// Hide loader when Paypal buttons are added.
		$document.on( 'DOMNodeInserted', obj.selectors.buttons, function() {
			$document.trigger( tribe.tickets.commerce.customEvents.hideLoader );
		} );
	}

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindScriptLoader = function() {

		const $script = $( obj.selectors.checkoutScript );

		if ( ! $script.length ) {
			$document.trigger( tribe.tickets.commerce.customEvents.hideLoader );
			return;
		}

		$script.on( 'load', function ( e ) {
			obj.setupButtons( e, $( tribe.tickets.commerce.selectors.checkoutContainer ) );
		} );
	}

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since 5.1.9
	 *
	 * @return {void}
	 */
	obj.ready = function () {
		obj.setupLoader();
		obj.bindScriptLoader();
	};

	$( obj.ready );

} )( jQuery, tribe.tickets.commerce.gateway.paypal.checkout );
