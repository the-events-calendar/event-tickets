/**
 * Tickets Commerce Square Webhooks JavaScript.
 *
 * @since TBD
 */

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.commerce = window.tec.tickets.commerce || {};
window.tec.tickets.commerce.square = window.tec.tickets.commerce.square || {};

/**
 * Tickets Commerce Square Webhooks object.
 *
 * @since TBD
 */
window.tec.tickets.commerce.square.webhooks = window.tec.tickets.commerce.square.webhooks || {};

/**
 * Initializes Tickets Commerce Square Webhooks.
 *
 * @since TBD
 *
 * @param {Document} document The document object.
 * @return {void}
 */
( ( $, document, obj ) => {
	'use strict';

	const { __ } = wp.i18n;

	/**
	 * Default strings used in the module.
	 *
	 * @since TBD
	 */
	const strings = {
		copied: __( 'Copied!', 'event-tickets' ),
		errorRegisteringWebhook: __( 'Failed to register webhook. Please try again.', 'event-tickets' ),
		errorGeneric: __( 'An error occurred. Please try again.', 'event-tickets' ),
	};

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 */
	const selectors = {
		container: '.tec-tickets-commerce-square-webhooks-container',
		copyButton: '.tec-tickets-commerce-square-copy-button',
		testWebhookButton: '.tec-tickets-commerce-square-test-webhook-button',
		registerWebhookButton: '#tec-tickets__admin-settings-square-webhook-register',
		statusMessage: '.tec-tickets-commerce-square-webhook-status',
		spinner: '.tec-tickets__admin-settings-square-webhook-spinner',
		testModeCheckbox: '#square-test-mode',
		liveFields: '.square-live-field',
		sandboxFields: '.square-sandbox-field',
	};

	/**
	 * Toggle the visibility of fields based on test mode.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const toggleTestMode = () => {
		const isTestMode = $( selectors.testModeCheckbox ).is( ':checked' );

		if ( isTestMode ) {
			$( selectors.liveFields ).closest( '.tribe-field' ).hide();
			$( selectors.sandboxFields ).closest( '.tribe-field' ).show();
		} else {
			$( selectors.liveFields ).closest( '.tribe-field' ).show();
			$( selectors.sandboxFields ).closest( '.tribe-field' ).hide();
		}
	};

	/**
	 * Initialize copy buttons functionality.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const initCopyButtons = () => {
		$( selectors.copyButton ).each( function() {
			const $button = $( this );
			const targetId = $button.data( 'clipboard-target' );

			if ( targetId ) {
				new window.tribe.clipboard( $button );
			}

			// Add success handling to the Click
			$button.on( 'click', function() {
				const $this = $( this );

				// Store the original text of the button
				const originalText = $this.text();

				// Change the text to indicate success
				$this.text( strings.copied );

				// Change it back after 2 seconds
				setTimeout( () => {
					$this.text( originalText );
				}, 2000 );
			} );
		} );
	};

	/**
	 * Handle webhook registration functionality.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	obj.registerWebhook = ( event ) => {
		event.preventDefault();

		const $registerButton = $( event.currentTarget );
		const $spinner = $registerButton.siblings( '.spinner' );
		const { nonce } = $registerButton.data();

		$registerButton.prop( 'disabled', true );
		$spinner.addClass( 'is-active' );

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'tec_tickets_commerce_square_register_webhook',
				nonce,
			},
			success: ( response ) => {
				if ( response.success ) {
					// Reload the page to refresh the status
					location.reload();
				} else {
					const message = response.data?.message || strings.errorRegisteringWebhook;
					alert( message );
					$registerButton.prop( 'disabled', false );
					$spinner.removeClass( 'is-active' );
				}
			},
			error: () => {
				alert( strings.errorGeneric );
				$registerButton.prop( 'disabled', false );
				$spinner.removeClass( 'is-active' );
			},
		} );
	};

	/**
	 * Bind events for Webhooks.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const bindEvents = () => {
		// Initialize the test webhook button
		$( selectors.testWebhookButton ).on( 'click', obj.testWebhook );

		// Initialize the test mode toggle
		$( selectors.testModeCheckbox ).on( 'change', toggleTestMode );

		// Initialize the webhook registration button
		$( selectors.registerWebhookButton ).on( 'click', obj.registerWebhook );
	};

	/**
	 * Initialize Webhooks.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const init = () => {
		initCopyButtons();
		bindEvents();
		// Trigger once on load to set initial state
		toggleTestMode();
	};

	// When the DOM is ready, initialize
	$( init );

} )( jQuery, document, window.tec.tickets.commerce.square.webhooks );
