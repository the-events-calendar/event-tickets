/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout, tribe_timepickers, wp */
import { _x } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { registerMiddlewares } from '@tec/common/tecApi';

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
		if ( typeof obj.tickets !== 'undefined' && obj.tickets.tecApiEndpoint ) {
			return obj.tickets.tecApiEndpoint;
		}
		if ( typeof obj.tickets !== 'undefined' && obj.tickets.ticketEndpoint ) {
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
	 * Converts a localized date string to YYYY-MM-DD format.
	 *
	 * @since TBD
	 *
	 * @param {string} dateStr The localized date string from datepicker.
	 *
	 * @return {string} The date in YYYY-MM-DD format, or empty string if invalid.
	 */
	obj.convertDateToApiFormat = function( dateStr ) {
		if ( ! dateStr ) {
			return '';
		}

		// Try to parse common date formats.
		const date = new Date( dateStr );
		if ( ! isNaN( date.getTime() ) ) {
			const year = date.getFullYear();
			const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
			const day = String( date.getDate() ).padStart( 2, '0' );
			return `${ year }-${ month }-${ day }`;
		}

		return '';
	};

	/**
	 * Converts a time string (12h or 24h) to HH:MM:SS format.
	 *
	 * @since TBD
	 *
	 * @param {string} timeStr     The time string (e.g., "12:00am", "14:30", "2:30pm").
	 * @param {string} defaultTime The default time if conversion fails.
	 *
	 * @return {string} The time in HH:MM:SS format.
	 */
	obj.convertTimeToApiFormat = function( timeStr, defaultTime ) {
		if ( ! timeStr ) {
			return defaultTime || '00:00:00';
		}

		// Check if already in 24h format (HH:MM or HH:MM:SS).
		const time24Match = timeStr.match( /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/ );
		if ( time24Match && ! /[ap]m/i.test( timeStr ) ) {
			const hours = String( time24Match[1] ).padStart( 2, '0' );
			const minutes = time24Match[2];
			const seconds = time24Match[3] || '00';
			return `${ hours }:${ minutes }:${ seconds }`;
		}

		// Parse 12h format (e.g., "12:00am", "1:30pm").
		const time12Match = timeStr.match( /^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(am|pm)$/i );
		if ( time12Match ) {
			let hours = parseInt( time12Match[1], 10 );
			const minutes = time12Match[2];
			const seconds = time12Match[3] || '00';
			const period = time12Match[4].toLowerCase();

			if ( period === 'pm' && hours !== 12 ) {
				hours += 12;
			} else if ( period === 'am' && hours === 12 ) {
				hours = 0;
			}

			return `${ String( hours ).padStart( 2, '0' ) }:${ minutes }:${ seconds }`;
		}

		return defaultTime || '00:00:00';
	};

	/**
	 * Gets the date in API format from a specific datepicker element.
	 *
	 * @since TBD
	 *
	 * @param {string} selector The jQuery selector for the datepicker input.
	 *
	 * @return {string} The date in YYYY-MM-DD format, or empty string if invalid.
	 */
	obj.getDateFromPicker = function( selector ) {
		const $picker = $( selector );
		if ( ! $picker.length ) {
			return '';
		}

		// Try jQuery UI datepicker method first.
		if ( $picker.datepicker && typeof $picker.datepicker === 'function' ) {
			try {
				const dateObj = $picker.datepicker( 'getDate' );
				if ( dateObj ) {
					const year = dateObj.getFullYear();
					const month = String( dateObj.getMonth() + 1 ).padStart( 2, '0' );
					const day = String( dateObj.getDate() ).padStart( 2, '0' );
					return `${ year }-${ month }-${ day }`;
				}
			} catch ( e ) {
				// Fall through to text value parsing.
			}
		}

		// Fallback to parsing the text value.
		return obj.convertDateToApiFormat( $picker.val() );
	};

	/**
	 * Maps Classic Editor form values to TEC REST API parameters.
	 *
	 * @since TBD
	 *
	 * @param {Object} formValues The form input values.
	 *
	 * @return {Object} The mapped API parameters.
	 */
	obj.mapFormValuesToApiParams = function( formValues ) {
		const params = {
			event: formValues.post_ID,
			type: obj.tickets.ticketType || 'tc-rsvp',
			title: formValues.ticket_name || '',
			price: 0,
			show_not_going: formValues.show_not_going === '1' ||
				formValues.show_not_going === 'on',
		};

		// Get dates from datepickers (converts to YYYY-MM-DD).
		const startDate = obj.getDateFromPicker( '#rsvp_start_date' );
		const endDate = obj.getDateFromPicker( '#rsvp_end_date' );

		// Convert times to HH:MM:SS format.
		const startTime = obj.convertTimeToApiFormat( formValues.rsvp_start_time, '00:00:00' );
		const endTime = obj.convertTimeToApiFormat( formValues.rsvp_end_time, '23:59:59' );

		// Combine date + time for start_date.
		if ( startDate ) {
			params.start_date = `${ startDate } ${ startTime }`;
		}

		// Combine date + time for end_date.
		if ( endDate ) {
			params.end_date = `${ endDate } ${ endTime }`;
		}

		// Handle capacity.
		const capacity = parseInt( formValues.rsvp_limit, 10 );
		if ( capacity > 0 ) {
			params.capacity = capacity;
			params.stock_mode = 'own';
		} else {
			params.stock_mode = 'unlimited';
		}

		return params;
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

		const formValues = obj.getFormInputValues();
		const apiParams = obj.mapFormValuesToApiParams( formValues );
		const rsvpId = formValues.rsvp_id;

		obj.submitButton( false );
		obj.loaderShow();

		// Determine endpoint and method: POST /tickets for create, PUT /tickets/{id} for update.
		const isUpdate = !! rsvpId;
		const endpoint = isUpdate
			? obj.tickets.tecApiEndpoint + '/' + rsvpId
			: obj.tickets.tecApiEndpoint;
		const method = isUpdate ? 'PUT' : 'POST';

		apiFetch( {
			path: endpoint,
			method: method,
			data: apiParams,
		} )
			.then( data => {
				obj.loaderHide();
				obj.submitButton( true );
				obj.handleTicketResponse( data );
			} )
			.catch( error => {
				obj.loaderHide();
				obj.submitButton( true );
				obj.handleApproveError( error );
			} );
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
		if ( ! confirm( wp.i18n._x( 'Are you sure you want to remove this RSVP? This action cannot be undone.', 'Confirmation message for deleting RSVP in admin panel.', 'event-tickets' ) ) ) {
			return;
		}

		const $rsvpIdInput = $( '#rsvp_id' );
		const rsvpId = $rsvpIdInput.val();

		if ( ! rsvpId ) {
			return;
		}

		obj.loaderShow();

		apiFetch( {
			url: obj.tickets.tecApiEndpoint + '/' + rsvpId,
			method: 'DELETE',
		} )
			.then( () => {
				obj.loaderHide();
				obj.handleRemoveResponse( {} );
			} )
			.catch( error => {
				obj.loaderHide();
				if ( error && error.data ) {
					obj.handleRemoveResponse( error.data );
				} else {
					obj.handleApproveError( error );
				}
			} );
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
		// TEC REST API returns { id: ... } on success.
		const ticketId = data.id || data.ticket_id;

		if ( ticketId ) {
			const $rsvpMetabox = $( '#tec_tickets_rsvp_metabox' );
			const $rsvpIdInput = $rsvpMetabox.find( '#rsvp_id' );
			if ( $rsvpIdInput.length ) {
				$rsvpIdInput.val( ticketId );
				$rsvpIdInput.trigger( 'change' );
				$rsvpMetabox.trigger( 'verify.dependency' );
			}

			const $rsvpSwitch = $( '.tec-tickets-rsvp-switch__wrap' );
			if ( $rsvpSwitch.length ) {
				$rsvpSwitch.addClass( obj.selectors.hiddenElement );
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
		// TEC REST API returns empty response on success, or { error: ... } on failure.
		if ( data && data.error ) {
			const errorMessage = data.message || data.error || _x( 'Failed to remove RSVP.', 'RSVP deletion error message', 'event-tickets' );
			window.alert( errorMessage );
			return;
		}

		const $rsvpIdInput = $( '#rsvp_id' );
		if ( $rsvpIdInput.length ) {
			$rsvpIdInput.val( '' );
			$rsvpIdInput.trigger( 'change' );
		}

		const $rsvpEnableCheckbox = $( obj.selectors.rsvpEnableCheckbox );
		if ( $rsvpEnableCheckbox.length ) {
			$rsvpEnableCheckbox.prop( 'checked', false );
			$rsvpEnableCheckbox.trigger( 'change' );
		}

		const $rsvpSwitch = $( '.tec-tickets-rsvp-switch__wrap' );
		if ( $rsvpSwitch.length ) {
			$rsvpSwitch.removeClass( obj.selectors.hiddenElement );
		}

		const $rsvpMetabox = $( '#tec_tickets_rsvp_metabox' );
		if ( $rsvpMetabox.length ) {
			$rsvpMetabox.trigger( 'verify.dependency' );
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
		registerMiddlewares();
		obj.bindEvents();
		obj.setupTimepickers();
	};

	$( obj.ready );
} )( jQuery, tribe.tickets.commerce.tickets );
