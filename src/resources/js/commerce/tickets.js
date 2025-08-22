/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout, tribe_timepickers */

/**
 * Makes sure we have all the required levels on the Tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
window.tribe = window.tribe || {};
window.tribe.tickets = window.tribe.tickets || {};

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
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		ticketForm: '.tec-event-tickets-from__wrap',
		submitButton: '#tc_ticket_form_save',
		removeButton: '#tc_ticket_form_remove',
		rsvpEnableCheckbox: '#tec_tickets_rsvp_enable',
		loader: '.tribe-common-c-loader',
		hiddenElement: 'tribe-common-a11y-hidden',
		rsvpMetabox: '#tec-tickets-commerce-rsvp',
		timepicker: '.tribe-timepicker:not(.ui-timepicker-input)',
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
			throw new Error( 'Tickets Endpoint URL is not available.' );
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

		// Get all form input values
		const formValues = obj.getFormInputValues();
		const nonce = { '_wpnonce' : obj.getEmbedNonce() };

		obj.submitButton( false );

		obj.loaderShow();

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
				obj.loaderHide();
				obj.handleTicketResponse( data );
			} )
			.catch( obj.handleApproveError );

		obj.submitButton( true );
	};

	/**
	 * Starts the process to delete an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the remove button.
	 */
	obj.handleRemove = async ( event ) => {
		event.preventDefault();

		// Show confirmation dialog.
		if ( ! confirm( 'Are you sure you want to remove this RSVP? This action cannot be undone.' ) ) {
			return;
		}

		// Get the RSVP ID and post ID from the form
		const $rsvpIdInput = $( '#rsvp_id' );
		const $postIdInput = $( '#post_ID' );
		
		const rsvpId = $rsvpIdInput.val();
		const postId = $postIdInput.val();

		if ( ! rsvpId || ! postId ) {
			return;
		}

		const nonce = obj.getEmbedNonce();
		const params = {
			'post_ID': postId,
			'rsvp_id': rsvpId,
			'_wpnonce': nonce
		};

		obj.loaderShow();

		fetch(
			obj.buildTicketsUrl( params ),
			{
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json',
				}
			}
		)
			.then( response => response.json() )
			.then( data => {
				obj.loaderHide();
				obj.handleRemoveResponse( data );
			} )
			.catch( obj.handleApproveError );
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
	 * Setup timepickers for RSVP metabox.
	 *
	 * @since TBD
	 */
	obj.setupTimepickers = () => {
		const $rsvpMetabox = $( obj.selectors.rsvpMetabox );
		if ( $rsvpMetabox.length && typeof tribe_timepickers !== 'undefined' ) {
			const $timepickers = $rsvpMetabox.find( obj.selectors.timepicker );
			tribe_timepickers.setup_timepickers( $timepickers );
		}
	};

	/**
	 * Show the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderShow = function (){
		const $loader = $( obj.selectors.rsvpMetabox ).find( obj.selectors.loader );

		$loader.removeClass( obj.selectors.hiddenElement );
	};

	/**
	 * Hide the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderHide = function () {
		const $loader = $( obj.selectors.rsvpMetabox ).find( obj.selectors.loader );

		$loader.addClass( obj.selectors.hiddenElement );
	};

	/**
	 * Handles the response from the ticket endpoint after RSVP creation.
	 *
	 * @since TBD
	 *
	 * @param {Object} data The response data from the server.
	 */
	obj.handleTicketResponse = function( data ) {
		if ( data.success && data.ticket_id ) {
			// Update the hidden RSVP ID field with the new ticket ID.
			const $rsvpMetabox = $( '#tec_tickets_rsvp_metabox' );
			const $rsvpIdInput = $rsvpMetabox.find( '#rsvp_id' );
			if ( $rsvpIdInput.length ) {
				$rsvpIdInput.val( data.ticket_id );

				// Trigger change event on the hidden input to notify dependency system.
				$rsvpIdInput.trigger( 'change' );

				// Verify dependencies on the wrapper after updating the RSVP ID.
				$rsvpMetabox.trigger( 'verify.dependency' );
			}
		}
	};

	/**
	 * Handles the response from the ticket endpoint after RSVP deletion.
	 *
	 * @since TBD
	 *
	 * @param {Object} data The response data from the server.
	 */
	obj.handleRemoveResponse = function( data ) {
		if ( data.success ) {
			// Clear the RSVP ID field.
			const $rsvpIdInput = $( '#rsvp_id' );
			if ( $rsvpIdInput.length ) {
				$rsvpIdInput.val( '' );

				// Trigger change event to notify dependency system.
				$rsvpIdInput.trigger( 'change' );
			}

			// Uncheck the RSVP enable checkbox.
			const $rsvpEnableCheckbox = $( obj.selectors.rsvpEnableCheckbox );
			if ( $rsvpEnableCheckbox.length ) {
				$rsvpEnableCheckbox.prop( 'checked', false );

				// Trigger change event to update dependent elements.
				$rsvpEnableCheckbox.trigger( 'change' );
			}
		}
	};

	/**
	 * Handles errors during the ticket save process.
	 *
	 * @since TBD
	 *
	 * @param {Error} error The error that occurred.
	 */
	obj.handleApproveError = function( error ) {
		// Error handling can be added here if needed.
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		$( document ).on( 'click', obj.selectors.submitButton, obj.handleSave );
		$( document ).on( 'click', obj.selectors.removeButton, obj.handleRemove );
	};

	/**
	 * When the page is ready.
	 *
	 * @since TBD
	 */
	obj.ready = () => {
		obj.bindEvents();
		obj.setupTimepickers();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.tickets );
