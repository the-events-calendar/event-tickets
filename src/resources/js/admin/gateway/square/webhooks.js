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
		statusMessage: '.tec-tickets-commerce-square-webhook-status',
		spinner: '.tec-tickets-commerce-square-webhook-spinner',
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
				setTimeout( function() {
					$this.text( originalText );
				}, 2000 );
			} );
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
