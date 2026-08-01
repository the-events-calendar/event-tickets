/* global tribe, jQuery, tecTicketsCommerceTickets, tribe_timepickers, wp */
import { registerMiddlewares } from '@tec/common/tecApi';
import apiFetch from '@wordpress/api-fetch';
import { doAction } from '@wordpress/hooks';
import { _x } from '@wordpress/i18n';

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

( ( $, obj ) => {
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
		removeButton: '#tc_ticket_form_remove',
		rsvpEnableCheckbox: '#tec_tickets_rsvp_enable',
		rsvpPanel: '#tec_event_tickets_rsvp_panel',
		rsvpOptions: '.tec-tickets-rsvp-form__options',
		loader: '.tribe-common-c-loader',
		hiddenElement: 'tribe-common-a11y-hidden',
		rsvpMetabox: '#tec-tickets-commerce-rsvp',
		timepicker: '.tribe-timepicker:not(.ui-timepicker-input)',
		postForm: '#post',
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
			path: obj.tickets.tecApiEndpoint + '/' + rsvpId,
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
	obj.loaderShow = function () {
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
	 * Resets Classic Editor RSVP form fields to their default empty state.
	 *
	 * @since TBD
	 */
	obj.resetRsvpForm = () => {
		const $panel = $( obj.selectors.rsvpPanel );

		if ( ! $panel.length ) {
			return;
		}

		$panel.find( 'input[type="checkbox"]' ).prop( 'checked', false ).trigger( 'change' );
		$panel.find( '#rsvp_limit' ).val( '' );

		const $options = $panel.find( obj.selectors.rsvpOptions );

		if ( $options.length ) {
			$options.find( 'input[type="checkbox"]' ).prop( 'checked', false ).trigger( 'change' );
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

		obj.resetRsvpForm();

		const $rsvpMetabox = $( '#tec_tickets_rsvp_metabox' );
		if ( $rsvpMetabox.length ) {
			$rsvpMetabox.trigger( 'verify.dependency' );
		}

		/**
		 * Fires after the RSVP has been removed from the Classic Editor metabox and the
		 * form has been reset to its default empty state.
		 *
		 * @since TBD
		 */
		doAction( 'tec.tickets.rsvp.classic.removed' );
	};

	/**
	 * Handles errors during the ticket remove process.
	 *
	 * @since TBD
	 *
	 * @param {Error} error The error that occurred.
	 */
	obj.handleApproveError = function( error ) {
		// Error handling can be added here if needed.
	};

	/**
	 * Validates RSVP fields before the post form is submitted.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The submit event from the post form.
	 */
	obj.validateBeforePostSave = ( event ) => {
		const $enableCheckbox = $( obj.selectors.rsvpEnableCheckbox );

		if ( ! $enableCheckbox.length || ! $enableCheckbox.is( ':checked' ) ) {
			return;
		}

		const $panel = $( obj.selectors.rsvpPanel );

		if ( ! $panel.length ) {
			return;
		}

		$panel.trigger( 'validation.tribe' );

		if ( typeof tribe !== 'undefined' && tribe.validation && tribe.validation.hasErrors( $panel ) ) {
			event.preventDefault();
		}
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		$( document ).on( 'click', obj.selectors.removeButton, obj.handleRemove );
		$( obj.selectors.postForm ).on( 'submit', obj.validateBeforePostSave );
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
