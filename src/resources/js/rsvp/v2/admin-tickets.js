/**
 * RSVP V2 Admin Tickets JavaScript
 *
 * Handles RSVP V2 metabox interactions in the WordPress admin.
 *
 * @since TBD
 */

/* global jQuery, TribeRsvpV2Admin */

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
 * Configures RSVP V2 Admin Object in the Global Tribe variable.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets.rsvp.v2.admin = {};

/**
 * Initializes in a Strict env the code that manages the RSVP V2 admin panel.
 *
 * @since TBD
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.rsvp.v2.admin
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
		container: '.tribe-tickets-rsvp-v2-metabox',
		enableToggle: '#tec_tickets_rsvp_v2_enabled',
		settingsContainer: '.tribe-tickets-rsvp-v2-settings',
		nameField: '#tec_tickets_rsvp_v2_name',
		capacityField: '#tec_tickets_rsvp_v2_capacity',
		showNotGoingField: '#tec_tickets_rsvp_v2_show_not_going',
		loadingClass: 'tribe-tickets-rsvp-v2-metabox--loading',
		errorClass: 'tribe-tickets-rsvp-v2-error',
		successClass: 'tribe-tickets-rsvp-v2-success',
	};

	/**
	 * Binds the enable toggle functionality.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.bindEnableToggle = function ( $container ) {
		const $toggle = $container.find( obj.selectors.enableToggle );
		const $settings = $container.find( obj.selectors.settingsContainer );

		$toggle.on( 'change', function () {
			if ( $( this ).is( ':checked' ) ) {
				$settings.slideDown( 200 );
			} else {
				$settings.slideUp( 200 );
			}
		} );
	};

	/**
	 * Binds name field validation.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.bindNameValidation = function ( $container ) {
		const $nameField = $container.find( obj.selectors.nameField );

		$nameField.on( 'blur', function () {
			const value = $( this ).val().trim();

			if ( ! value && $container.find( obj.selectors.enableToggle ).is( ':checked' ) ) {
				obj.showFieldError( $( this ), TribeRsvpV2Admin.i18n.nameRequired );
			} else {
				obj.clearFieldError( $( this ) );
			}
		} );
	};

	/**
	 * Binds capacity field formatting.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.bindCapacityField = function ( $container ) {
		const $capacityField = $container.find( obj.selectors.capacityField );

		$capacityField.on( 'input', function () {
			// Only allow non-negative integers.
			let value = $( this ).val();
			value = value.replace( /[^0-9]/g, '' );
			$( this ).val( value );
		} );
	};

	/**
	 * Shows a field error message.
	 *
	 * @since TBD
	 * @param {jQuery} $field The field element.
	 * @param {string} message The error message.
	 * @return {void}
	 */
	obj.showFieldError = function ( $field, message ) {
		obj.clearFieldError( $field );

		const $error = $( '<span>' )
			.addClass( obj.selectors.errorClass.replace( '.', '' ) )
			.text( message );

		$field.after( $error );
		$field.addClass( 'error' );
	};

	/**
	 * Clears a field error message.
	 *
	 * @since TBD
	 * @param {jQuery} $field The field element.
	 * @return {void}
	 */
	obj.clearFieldError = function ( $field ) {
		$field.removeClass( 'error' );
		$field.siblings( '.' + obj.selectors.errorClass.replace( '.', '' ) ).remove();
	};

	/**
	 * Shows a loading state on the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.showLoading = function ( $container ) {
		$container.addClass( obj.selectors.loadingClass );
	};

	/**
	 * Hides the loading state on the container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.hideLoading = function ( $container ) {
		$container.removeClass( obj.selectors.loadingClass );
	};

	/**
	 * Initializes the RSVP V2 admin panel.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP V2 container.
	 * @return {void}
	 */
	obj.init = function ( $container ) {
		obj.bindEnableToggle( $container );
		obj.bindNameValidation( $container );
		obj.bindCapacityField( $container );
	};

	/**
	 * Handles the initialization of RSVP V2 admin containers.
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

} )( jQuery, tribe.tickets.rsvp.v2.admin );
