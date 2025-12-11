/**
 * RSVP V2 Manager JavaScript
 *
 * Handles AJAX requests for RSVP V2 submissions.
 *
 * @since TBD
 */

/* global jQuery, TribeRsvpV2Block */

/**
 * Makes sure we have all the required levels on the Tribe Object.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.tickets.rsvp = tribe.tickets.rsvp || {};
tribe.tickets.rsvp.v2 = tribe.tickets.rsvp.v2 || {};

/**
 * Configures RSVP V2 Manager Object in the Global Tribe variable.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets.rsvp.v2.manager = {};

/**
 * Initializes in a Strict env the code that manages RSVP V2 AJAX requests.
 *
 * @since TBD
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.rsvp.v2.manager
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';

	/**
	 * The REST API endpoint URL.
	 *
	 * @since TBD
	 * @type {string}
	 */
	obj.restUrl = TribeRsvpV2Block.restUrl || '';

	/**
	 * Makes an AJAX request to submit an RSVP.
	 *
	 * @since TBD
	 * @param {Object} data The request data.
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.request = function ( data, $container ) {
		const block = tribe.tickets.rsvp.v2.block;

		block.showLoading( $container );
		block.clearMessages( $container );

		$.ajax( {
			url: obj.restUrl + 'rsvp/order',
			method: 'POST',
			data: data,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', TribeRsvpV2Block.nonces.rsvpHandle );
			},
		} )
			.done( function ( response ) {
				obj.handleSuccess( response, $container );
			} )
			.fail( function ( jqXHR ) {
				obj.handleError( jqXHR, $container );
			} )
			.always( function () {
				block.hideLoading( $container );
			} );
	};

	/**
	 * Handles a successful RSVP submission.
	 *
	 * @since TBD
	 * @param {Object} response The response data.
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.handleSuccess = function ( response, $container ) {
		const block = tribe.tickets.rsvp.v2.block;

		block.hideForm( $container );
		block.showSuccess( $container, TribeRsvpV2Block.i18n.success );

		// Trigger a custom event for extensions.
		$container.trigger( 'tec-tickets-rsvp-v2-success', [ response ] );

		// Update attendance counts if present.
		obj.updateAttendance( response, $container );
	};

	/**
	 * Handles an error in the RSVP submission.
	 *
	 * @since TBD
	 * @param {Object} jqXHR The jQuery XHR object.
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.handleError = function ( jqXHR, $container ) {
		const block = tribe.tickets.rsvp.v2.block;

		let message = TribeRsvpV2Block.i18n.error;

		// Try to get a more specific error message.
		if ( jqXHR.responseJSON && jqXHR.responseJSON.message ) {
			message = jqXHR.responseJSON.message;
		} else if ( jqXHR.status === 403 ) {
			message = TribeRsvpV2Block.i18n.full || message;
		}

		block.showError( $container, message );

		// Trigger a custom event for extensions.
		$container.trigger( 'tec-tickets-rsvp-v2-error', [ jqXHR ] );
	};

	/**
	 * Updates attendance information after a successful submission.
	 *
	 * @since TBD
	 * @param {Object} response The response data.
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.updateAttendance = function ( response, $container ) {
		if ( ! response.attendance ) {
			return;
		}

		const $attendance = $container.find( '.tribe-tickets__rsvp-v2-attendance' );

		if ( $attendance.length && response.attendance.going !== undefined ) {
			$attendance.find( '.attendance-going' ).text( response.attendance.going );
		}

		if ( $attendance.length && response.attendance.not_going !== undefined ) {
			$attendance.find( '.attendance-not-going' ).text( response.attendance.not_going );
		}
	};

	/**
	 * Updates the RSVP status for an existing attendee.
	 *
	 * @since TBD
	 * @param {number} attendeeId The attendee ID.
	 * @param {string} newStatus The new status (yes or no).
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.updateStatus = function ( attendeeId, newStatus, $container ) {
		const block = tribe.tickets.rsvp.v2.block;

		block.showLoading( $container );
		block.clearMessages( $container );

		$.ajax( {
			url: obj.restUrl + 'rsvp/order/' + attendeeId,
			method: 'PUT',
			data: {
				rsvp_status: newStatus,
			},
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', TribeRsvpV2Block.nonces.rsvpHandle );
			},
		} )
			.done( function ( response ) {
				obj.handleSuccess( response, $container );
			} )
			.fail( function ( jqXHR ) {
				obj.handleError( jqXHR, $container );
			} )
			.always( function () {
				block.hideLoading( $container );
			} );
	};

} )( jQuery, tribe.tickets.rsvp.v2.manager );
