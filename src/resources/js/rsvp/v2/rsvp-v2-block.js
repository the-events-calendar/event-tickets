/**
 * RSVP V2 Block JavaScript
 *
 * Handles RSVP V2 block interactions on the frontend.
 *
 * @since TBD
 */

/* global jQuery, TribeRsvpV2Block, wp */

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
 * Configures RSVP V2 Block Object in the Global Tribe variable.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets.rsvp.v2.block = {};

/**
 * Initializes in a Strict env the code that manages the RSVP V2 block.
 *
 * @since TBD
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.rsvp.v2.block
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';

	const $document = $( document );

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 * @type {Object}
	 */
	obj.selectors = {
		container: '.tribe-tickets__rsvp-v2-wrapper',
		rsvpForm: 'form[name~="tribe-tickets-rsvp-v2-form"]',
		goingButton: '.tribe-tickets__rsvp-v2-button--going',
		notGoingButton: '.tribe-tickets__rsvp-v2-button--not-going',
		cancelButton: '.tribe-tickets__rsvp-v2-form-button--cancel',
		submitButton: '.tribe-tickets__rsvp-v2-form-button--submit',
		formContainer: '.tribe-tickets__rsvp-v2-form',
		actionsContainer: '.tribe-tickets__rsvp-v2-actions',
		successMessage: '.tribe-tickets__rsvp-v2-success',
		errorMessage: '.tribe-tickets__rsvp-v2-error',
		hiddenElement: '.tribe-common-a11y-hidden',
		loadingClass: 'tribe-tickets__rsvp-v2-wrapper--loading',
	};

	/**
	 * Gets the RSVP ID from the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {number} The RSVP ticket ID.
	 */
	obj.getRsvpId = function ( $container ) {
		return $container.data( 'rsvp-id' ) || 0;
	};

	/**
	 * Gets the post ID from the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {number} The post ID.
	 */
	obj.getPostId = function ( $container ) {
		return $container.data( 'post-id' ) || 0;
	};

	/**
	 * Shows the RSVP form.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @param {string} step The step (going or not-going).
	 * @return {void}
	 */
	obj.showForm = function ( $container, step ) {
		const $form = $container.find( obj.selectors.formContainer );
		const $actions = $container.find( obj.selectors.actionsContainer );

		$form.data( 'step', step );
		$actions.addClass( obj.selectors.hiddenElement.replace( '.', '' ) );
		$form.removeClass( obj.selectors.hiddenElement.replace( '.', '' ) );
	};

	/**
	 * Hides the RSVP form.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.hideForm = function ( $container ) {
		const $form = $container.find( obj.selectors.formContainer );
		const $actions = $container.find( obj.selectors.actionsContainer );

		$form.addClass( obj.selectors.hiddenElement.replace( '.', '' ) );
		$actions.removeClass( obj.selectors.hiddenElement.replace( '.', '' ) );
	};

	/**
	 * Shows a loading state on the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.showLoading = function ( $container ) {
		$container.addClass( obj.selectors.loadingClass );
	};

	/**
	 * Hides the loading state on the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.hideLoading = function ( $container ) {
		$container.removeClass( obj.selectors.loadingClass );
	};

	/**
	 * Shows a success message.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @param {string} message The success message.
	 * @return {void}
	 */
	obj.showSuccess = function ( $container, message ) {
		obj.clearMessages( $container );

		const $success = $( '<div>' )
			.addClass( obj.selectors.successMessage.replace( '.', '' ) )
			.text( message );

		$container.append( $success );
	};

	/**
	 * Shows an error message.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @param {string} message The error message.
	 * @return {void}
	 */
	obj.showError = function ( $container, message ) {
		obj.clearMessages( $container );

		const $error = $( '<div>' )
			.addClass( obj.selectors.errorMessage.replace( '.', '' ) )
			.text( message );

		$container.append( $error );
	};

	/**
	 * Clears all messages.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.clearMessages = function ( $container ) {
		$container.find( obj.selectors.successMessage ).remove();
		$container.find( obj.selectors.errorMessage ).remove();
	};

	/**
	 * Binds events for the going button.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindGoing = function ( $container ) {
		const $goingButton = $container.find( obj.selectors.goingButton );

		$goingButton.on( 'click', function ( event ) {
			event.preventDefault();
			obj.showForm( $container, 'going' );
		} );
	};

	/**
	 * Binds events for the not going button.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindNotGoing = function ( $container ) {
		const $notGoingButton = $container.find( obj.selectors.notGoingButton );

		$notGoingButton.on( 'click', function ( event ) {
			event.preventDefault();
			obj.showForm( $container, 'not-going' );
		} );
	};

	/**
	 * Binds events for the cancel button.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindCancel = function ( $container ) {
		const $cancelButton = $container.find( obj.selectors.cancelButton );

		$cancelButton.on( 'click', function ( event ) {
			event.preventDefault();
			obj.hideForm( $container );
			obj.clearMessages( $container );
		} );
	};

	/**
	 * Binds events for the form submission.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.bindFormSubmit = function ( $container ) {
		const $form = $container.find( obj.selectors.formContainer );

		$form.on( 'submit', function ( event ) {
			event.preventDefault();

			const step = $form.data( 'step' );
			const rsvpId = obj.getRsvpId( $container );
			const postId = obj.getPostId( $container );

			if ( ! rsvpId ) {
				obj.showError( $container, TribeRsvpV2Block.i18n.error );
				return;
			}

			const formData = {
				ticket_id: rsvpId,
				rsvp_status: step === 'going' ? 'yes' : 'no',
				name: $form.find( 'input[name="rsvp_name"]' ).val(),
				email: $form.find( 'input[name="rsvp_email"]' ).val(),
				quantity: 1,
			};

			// Use the manager to submit the request.
			if ( tribe.tickets.rsvp.v2.manager ) {
				tribe.tickets.rsvp.v2.manager.request( formData, $container );
			}
		} );
	};

	/**
	 * Initializes a single RSVP V2 block.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.init = function ( $container ) {
		obj.bindGoing( $container );
		obj.bindNotGoing( $container );
		obj.bindCancel( $container );
		obj.bindFormSubmit( $container );
	};

	/**
	 * Handles the initialization of RSVP V2 blocks.
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.ready = function () {
		$( obj.selectors.container ).each( function () {
			obj.init( $( this ) );
		} );
	};

	// Initialize on document ready.
	$document.ready( obj.ready );

	// Allow re-initialization via WordPress hooks.
	if ( typeof wp !== 'undefined' && wp.hooks ) {
		wp.hooks.addAction( 'tec.tickets.rsvp.v2.init', 'tec.tickets', obj.ready );
	}

} )( jQuery, tribe.tickets.rsvp.v2.block );
