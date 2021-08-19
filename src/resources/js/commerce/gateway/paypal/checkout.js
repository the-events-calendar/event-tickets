/* global tribe, jQuery, paypal, tecTicketsCommerceGatewayPayPalCheckout */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.paypal = tribe.tickets.commerce.gateway.paypal || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.paypal.checkout = {};

/**
 * Initializes in a Strict env the code that manages the checkout for PayPal.
 *
 * @since TBD
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
	 * @since TBD
	 *
	 * @type {string}
	 */
	obj.orderEndpointUrl = tecTicketsCommerceGatewayPayPalCheckout.orderEndpoint;

	/**
	 * PayPal Checkout Selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {};

	/**
	 * Handles the creation of the orders via PayPal.
	 *
	 * @since TBD
	 *
	 * @param {Object} data PayPal data passed to this method.
	 * @param {Object} actions PayPal actions available on order creation.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrder = function ( data, actions ) {
		return fetch(
			obj.orderEndpointUrl,
			{
				method: 'POST'
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
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {string}
	 */
	obj.handleCreateOrderSuccess = function ( data ) {
		return data.id;
	};

	/**
	 * When a failed request is completed to our Create Order endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrderFail = function ( data ) {

	};

	/**
	 * When a error happens on the fetch request to our Create Order endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} error Which error the fetch() threw on requesting our endpoints.
	 *
	 * @return {void}
	 */
	obj.handleCreateOrderError = function ( error ) {

	};

	/**
	 * Handles the Approval of the orders via PayPal.
	 *
	 * @since TBD
	 *
	 * @param {Object} data PayPal data passed to this method.
	 * @param {Object} actions PayPal actions available on approve.
	 *
	 * @return {void}
	 */
	obj.handleApprove = function ( data, actions ) {
		/**
		 * @todo On approval we receive a bit more than just the orderID on the data object
		 *       we should be passing those to the BE.
		 */
		return fetch(
			obj.orderEndpointUrl + '/' + data.orderID,
			{
				method: 'POST'
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
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleApproveSuccess = function ( data ) {

	};

	/**
	 * When a failed request is completed to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {void}
	 */
	obj.handleApproveFail = function ( data ) {

	};

	/**
	 * When a error happens on the fetch request to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} error Which error the fetch() threw on requesting our endpoints.
	 *
	 * @return {void}
	 */
	obj.handleApproveError = function ( error ) {

	};

	/**
	 * Unbinds the description toggle.
	 *
	 * @since TBD
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
			createOrder: obj.handleCreateOrder,
			onApprove: obj.handleApprove
		};

		return configs;
	};

	/**w
	 * Setup the Buttons for PayPal Checkout..
	 *
	 * @since TBD
	 *
	 * @param  {Event}   event      event object for 'afterSetup.tribeTicketsCommerceCheckout' event
	 * @param  {int}     index      jQuery.each index param from 'afterSetup.tribeTicketsCommerceCheckout' event.
	 * @param  {jQuery}  $container jQuery object of checkout container.
	 *
	 * @return {void}
	 */
	obj.setupButtons = function ( event, index, $container ) {
		paypal.Buttons( obj.getButtonConfig( $container ) ).render( '#paypal-button-container' );
	};

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function () {
		$document.on( 'afterSetup.tribeTicketsCommerceCheckout', obj.setupButtons );
	};

	$( obj.ready );

} )( jQuery, tribe.tickets.commerce );
