/* global tribe, jQuery, tecTicketsCommerceGatewayPayPalSignup, ajaxurl */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
window.tribe.tickets = window.tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
window.tribe.tickets.commerce = window.tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
window.tribe.tickets.commerce.gateway = window.tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
window.tribe.tickets.commerce.gateway.paypal = window.tribe.tickets.commerce.gateway.paypal || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.2.0
 *
 * @type   {Object}
 */
window.tribe.tickets.commerce.gateway.paypal.signup = {};

/**
 * Initializes in a Strict env the code that manages the checkout for PayPal.
 *
 * @since 5.2.0
 *
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.commerce.gateway.paypal.checkout
 *
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';
	/**
	 * PayPal Signup nonce.
	 *
	 * @since 5.2.0
	 *
	 * @type {string}
	 */
	obj.onboardNonce = tecTicketsCommerceGatewayPayPalSignup.onboardNonce;

	/**
	 * PayPal Refresh Connect URL nonce.
	 *
	 * @since 5.2.0
	 *
	 * @type {string}
	 */
	obj.refreshConnectNonce = tecTicketsCommerceGatewayPayPalSignup.refreshConnectNonce;

	/**
	 * PayPal Signup handling endpoint.
	 *
	 * @since 5.2.0
	 *
	 * @type {string}
	 */
	obj.onboardingEndpointUrl = tecTicketsCommerceGatewayPayPalSignup.onboardingEndpointUrl;

	/**
	 * PayPal Signup Selectors.
	 *
	 * @since 5.2.0
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		container: '.tec-tickets__admin-settings-tickets-commerce-gateway-signup-settings',
		button: '.tec-tickets__admin-settings-tickets-commerce-gateway-connect-button-link',
		error: '.tec-tickets__admin-settings-tickets-commerce-gateway-connect-error',
		countryField: '[name="tec-tickets-commerce-gateway-paypal-merchant-country"]',
	};

	/**
	 * Sequence number of the most recent country refresh.
	 *
	 * Responses can arrive out of order, and a stale one would point the button at a country the seller
	 * has already moved off.
	 *
	 * @since TBD
	 *
	 * @type {number}
	 */
	obj.refreshRequest = 0;

	/**
	 * Handles the singup onboarding of customers to PayPal.
	 *
	 * @since 5.2.0
	 *
	 * @param {string} authCode PayPal data passed to this method.
	 * @param {string} sharedId jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.onboardedCallback = ( authCode, sharedId ) => {
		fetch( obj.onboardingEndpointUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/json',
			},
			body: JSON.stringify( {
				auth_code: authCode,
				shared_id: sharedId,
				nonce: obj.onboardNonce,
			} ),
		} );
	};

	/**
	 * When the country field changes we need to refresh the link.
	 *
	 * @since 5.2.0
	 *
	 * @param event {Event}
	 *
	 * @return {void}
	 */
	obj.onCountryChange = function ( event ) {
		const $field = $( this );
		const $container = $field.closest( obj.selectors.container );
		const $button = $container.find( obj.selectors.button );
		const $error = $container.find( obj.selectors.error );
		const request = ++obj.refreshRequest;

		obj.disableButton( $button );

		fetch(
			ajaxurl +
				'?action=tec_tickets_commerce_gateway_paypal_refresh_connect_url&nonce=' +
				obj.refreshConnectNonce +
				'&country_code=' +
				$field.val(),
			{
				method: 'GET',
				headers: {
					'content-type': 'application/json',
				},
			}
		) // eslint-disable-line max-len
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( res ) {
				if ( request !== obj.refreshRequest ) {
					return;
				}

				if ( true !== res.success || ! res.data || ! res.data.new_url ) {
					// The stored link is for the country the seller just changed away from, so keep it unclickable.
					$error.show();
					return;
				}

				// Without a button there is nothing to point at the new link, and the notice says to reload.
				if ( ! $button.length ) {
					return;
				}

				$error.hide();
				$button.attr( 'href', res.data.new_url );
				obj.enableButton( $button );
			} )
			.catch( function () {
				if ( request !== obj.refreshRequest ) {
					return;
				}

				$error.show();
			} );
	};

	/**
	 * Makes the connect button unusable, including for keyboard and assistive technology.
	 *
	 * The `disabled` class only sets `pointer-events: none`, which leaves the anchor focusable and
	 * activatable with Enter.
	 *
	 * @since TBD
	 *
	 * @param {Object} $button jQuery object of the connect button.
	 *
	 * @return {void}
	 */
	obj.disableButton = ( $button ) => {
		$button.addClass( 'disabled' ).attr( 'aria-disabled', 'true' ).attr( 'tabindex', '-1' );
	};

	/**
	 * Restores the connect button.
	 *
	 * @since TBD
	 *
	 * @param {Object} $button jQuery object of the connect button.
	 *
	 * @return {void}
	 */
	obj.enableButton = ( $button ) => {
		$button.removeClass( 'disabled' ).removeAttr( 'aria-disabled' ).removeAttr( 'tabindex' );
	};

	/**
	 * Setup the triggers for Ticket Commerce loader view.
	 *
	 * @since 5.2.0
	 *
	 * @return {void}
	 */
	obj.setup = () => {
		// Hide loader when Paypal buttons are added.
		$( obj.selectors.countryField ).on( 'change', obj.onCountryChange );
	};

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since 5.2.0
	 *
	 * @return {void}
	 */
	obj.ready = () => {
		obj.setup();
	};

	$( obj.ready );
} )( jQuery, window.tribe.tickets.commerce.gateway.paypal.signup );

/**
 * Do not remove this, since PayPal codebase doesn't support a direct reference to how our objects are structured.
 *
 * @type {tribe.tickets.commerce.gateway.paypal.signup.onboardedCallback}
 *
 * @since 5.2.0
 */
window.tecTicketsCommerceGatewayPayPalSignupCallback = window.tribe.tickets.commerce.gateway.paypal.signup.onboardedCallback;
