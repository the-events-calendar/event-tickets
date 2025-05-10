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
	 * Check and return embed URL from tec_event_pro_calendar_embed_data if available.
	 *
	 * @since TBD
	 *
	 * @param string embedUrl The default embed URL.
	 *
	 * @return string The updated embed URL if available, otherwise the passed default URL.
	 */
	obj.getUpdatedTicketUrl = function( ticketsURL ) {
		if (
			typeof obj.tickets !== 'undefined' &&
			obj.tickets.ticketEndpoint
		) {
			ticketsURL = obj.tickets.ticketEndpoint;
		}
		return ticketsURL;
	}

	/**
	 * Build the embed URL using default or filtered query arguments.
	 *
	 * @since TBD
	 *
	 * @param object params The query parameters to be added to the URL.
	 *
	 * @return string|Error The complete URL with query arguments, or an error if the URL is empty.
	 */
	obj.buildTicketsUrl = function( params ) {
		let ticketsURL = '';
		ticketsURL = obj.getUpdatedTicketUrl( ticketsURL );

		if ( !ticketsURL ) {
			throw new Error( _x( 'Tickets Endpoint URL is not available.', 'Tickets REST Endpoint message when url for REST endpoint is not available.', 'event-tickets' ) );
		}

		const url = new URL( ticketsURL );
		url.search = new URLSearchParams( params ).toString();

		return url.toString();
	}

	/**
	 * Check and return REST nonce from tecTicketsCommerceTickets if available.
	 *
	 * @since TBD
	 *
	 * @return string The embed nonce.
	 */
	obj.getEmbedNonce = function() {
		let RESTNonce = '';

		if (
			typeof tecTicketsCommerceTickets !== 'undefined' &&
			tecTicketsCommerceTickets.nonce
		) {
			RESTNonce = tecTicketsCommerceTickets.nonce;
		}
		return RESTNonce;
	}

	/**
	 * Starts the process to submit a ticket for saving.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handleSave = async ( event ) => {
		event.preventDefault();

		//@todo show error messages if missing required fields

		console.log('button clicked');
		console.log(obj.tickets);

		// Get all form input values
		const formValues = obj.getFormInputValues();
		const nonce = { '_wpnonce' : obj.getEmbedNonce() };
		const ticketUrl = obj.getUpdatedTicketUrl( [] );

		// Log the form values (for debugging)
		console.log('Form values:', { ...formValues, ...nonce });
		console.log('nonce:', nonce);
		console.log('ticketUrl:', ticketUrl);

		obj.submitButton( false );

		//@todo submit details to rest endpoint and handle success and error

		const body = {
			'_wpnonce': nonce,
			formValues
		};

		fetch(
				obj.buildTicketsUrl( { ...formValues, ...nonce } ),
			{
				method: 'POST',
				headers: {
					//'X-WP-Nonce': $container.find( tribe.tickets.commerce.selectors.nonce ).val(),
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( body ),
			}
		)
			.then( response => response.json() )
			.then( data => {
				if ( data.success ) {
					//return obj.handleApproveSuccess( data, actions, $container );
				} else {
					//return obj.handleApproveFail( data, actions, $container );
				}
			} )
			.catch( obj.handleApproveError );

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
	 * Gets all input values from the ticket form.
	 *
	 * @since TBD
	 *
	 * @return {Object} An object containing all input values from the form.
	 */
	obj.getFormInputValues = () => {
		const $form = $( obj.selectors.ticketForm );
		const values = {};

		// Get all inputs, selects, and textareas
		const $inputs = $form.find('input, select, textarea');

		$inputs.each(function() {
			const $input = $(this);
			const name = $input.attr('name');

			// Skip if no name attribute
			if (!name) {
				return;
			}

			// Handle different input types
			if ($input.is(':checkbox')) {
				if ($input.is(':checked')) {
					// For checkboxes with the same name (groups), store as array
					if (name.endsWith('[]') || $form.find('input[name="' + name + '"]').length > 1) {
						if (!values[name]) {
							values[name] = [];
						}
						values[name].push($input.val());
					} else {
						values[name] = $input.val();
					}
				} else if (!values[name] && !name.endsWith('[]')) {
					// Set unchecked checkboxes to empty or false if not part of a group
					values[name] = '';
				}
			} else if ($input.is(':radio')) {
				if ($input.is(':checked')) {
					values[name] = $input.val();
				}
			} else if ($input.is('select[multiple]')) {
				values[name] = $input.val() || [];
			} else {
				values[name] = $input.val();
			}
		});

		return values;
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
