/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout */

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.tickets = {};

( ( $, obj, Stripe, ky ) => {
	'use strict';

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.tickets = tecTicketsCommerceTickets;

	/**
	 * Checkout Selectors.
	 *
	 * @since TBD
	 * @since 5.19.3 Changed form selector to target form surrounding TicketsCommerce fields.
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		ticketForm: '.tec-event-tickets-from__wrap',
		submitButton: '#tc_ticket_form_save',
		hiddenElement: '.tribe-common-a11y-hidden',
	};

	/**
	 * Starts the process to submit a ticket for saving.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handleSave = async ( event ) => {
		event.preventDefault();

		//@todo get ticket details and show error messages if missing

		console.log('button clicked');
		console.log(obj.tickets);

		obj.submitButton( false );

		//@todo submit details to rest endpoint and handle success and error

		obj.submitButton( true );
	};

	/**
	 * Toggle the submit button enabled/disabled
	 *
	 * @since TBD
	 *
	 * @param enable
	 */
	obj.submitButton = ( enable ) => {
		$( obj.selectors.submitButton ).prop( 'disabled', ! enable );
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		$( document ).on( 'click', obj.selectors.submitButton, obj.handleSave );
	};

	/**
	 * When the page is ready.
	 *
	 * @since TBD
	 */
	obj.ready = () => {
		obj.bindEvents();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.tickets );
