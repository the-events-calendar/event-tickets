window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.commerce = window.tec.tickets.commerce || {};
window.tec.tickets.commerce.square = window.tec.tickets.commerce.square || {};
window.tec.tickets.commerce.square.webhooks = window.tec.tickets.commerce.square.webhooks || {};

(function(webhooks) {
	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since 5.3.0
	 *
	 * @type  {Object}
	 */
	webhooks.selectors = {
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
	 * Handles the initialization of the scripts.
	 *
	 * @since 5.3.0
	 *
	 * @return {void}
	 */
	webhooks.init = function() {
		// Initialize the copy buttons
		$( webhooks.selectors.copyButton ).each( function() {
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
				$this.text( 'Copied!' );

				// Change it back after 2 seconds
				setTimeout( function() {
					$this.text( originalText );
				}, 2000 );
			} );
		} );

		// Initialize the test webhook button
		$( webhooks.selectors.testWebhookButton ).on( 'click', webhooks.testWebhook );

		// Initialize the test mode toggle
		$( webhooks.selectors.testModeCheckbox ).on( 'change', webhooks.toggleTestMode );
		// Trigger once on load to set initial state
		webhooks.toggleTestMode();
	};

	/**
	 * Toggle the visibility of fields based on test mode.
	 *
	 * @since 5.3.0
	 *
	 * @return {void}
	 */
	webhooks.toggleTestMode = function() {
		const isTestMode = $( webhooks.selectors.testModeCheckbox ).is( ':checked' );

		if ( isTestMode ) {
			$( webhooks.selectors.liveFields ).closest( '.tribe-field' ).hide();
			$( webhooks.selectors.sandboxFields ).closest( '.tribe-field' ).show();
		} else {
			$( webhooks.selectors.liveFields ).closest( '.tribe-field' ).show();
			$( webhooks.selectors.sandboxFields ).closest( '.tribe-field' ).hide();
		}
	};

}(window.tec.tickets.commerce.square.webhooks));
