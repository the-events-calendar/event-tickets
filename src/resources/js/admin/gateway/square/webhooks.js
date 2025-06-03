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
		registerWebhookTrigger: '.tec-tickets__admin-settings-square-webhook-register-trigger',
		statusMessage: '.tec-tickets-commerce-square-webhook-status',
		spinner: '.tec-tickets__admin-settings-square-webhook-spinner',
		testModeCheckbox: '#square-test-mode',
		liveFields: '.square-live-field',
		sandboxFields: '.square-sandbox-field',
		fixWebhookButton: '.notice .button-primary[href*="admin.php?page=tec-tickets-settings&tab=payments&section=square"]',
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
	 * Handle webhook registration from admin notice.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	obj.registerWebhookFromNotice = ( event ) => {
		event.preventDefault();

		const $link = $( event.currentTarget );
		const $notice = $link.closest( '.notice' );

		// Add a spinner to the notice
		if ( ! $notice.find( '.spinner' ).length ) {
			$link.after( '<span class="spinner is-active" style="float: none; margin-top: 0;"></span>' );
		}

		// Disable the link
		$link.css( 'pointer-events', 'none' ).css( 'opacity', '0.5' );

		// Get the nonce from the clicked link
		let nonce = $link.data( 'nonce' );

		// If no nonce found in the link, try to get it from the hidden element in the page
		if ( ! nonce ) {
			const $nonceElement = $( '.tec-tickets__admin-settings-square-webhook-nonce' );
			if ( $nonceElement.length ) {
				nonce = $nonceElement.data( 'nonce' );
			}
		}

		// If still no nonce found, try to get it from the register button if it exists on the page
		if ( ! nonce ) {
			const $registerButton = $( selectors.registerWebhookButton );
			if ( $registerButton.length ) {
				nonce = $registerButton.data( 'nonce' );
			}
		}

		// If no nonce found, show an error
		if ( ! nonce ) {
			console.error( 'No webhook registration nonce found' );
			alert( strings.errorGeneric );
			// Reset the link
			$link.css( 'pointer-events', '' ).css( 'opacity', '' );
			$notice.find( '.spinner' ).remove();
			return;
		}

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
					// Reset the link
					$link.css( 'pointer-events', '' ).css( 'opacity', '' );
					$notice.find( '.spinner' ).remove();
				}
			},
			error: () => {
				alert( strings.errorGeneric );
				// Reset the link
				$link.css( 'pointer-events', '' ).css( 'opacity', '' );
				$notice.find( '.spinner' ).remove();
			},
		} );
	};

	/**
	 * Handle click on the Fix Webhook Configuration button in notices.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	obj.handleFixWebhookClick = ( event ) => {
		event.preventDefault();

		const $button = $( event.currentTarget );

		// Add spinner next to the button
		if ( ! $button.siblings( '.spinner' ).length ) {
			$button.after( '<span class="spinner is-active" style="float: none; margin-top: 0;"></span>' );
		}

		// Disable the button
		$button.prop( 'disabled', true );

		// Get the nonce directly from the button
		const nonce = $button.data( 'nonce' );

		// If no nonce found, redirect to the settings page
		if ( ! nonce ) {
			window.location = $button.attr( 'href' );
			return;
		}

		// If we have a nonce, register the webhook directly
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'tec_tickets_commerce_square_register_webhook',
				nonce,
			},
			success: ( response ) => {
				if ( response.success ) {
					// Show success notice and reload
					$button.closest( '.notice' )
						.removeClass( 'notice-error' )
						.addClass( 'notice-success' )
						.find( 'p:first' )
						.html( '<strong>' + __( 'Square webhook registered successfully!', 'event-tickets' ) + '</strong>' );

					setTimeout( () => {
						location.reload();
					}, 1500 );
				} else {
					// Redirect to settings page on error
					window.location = $button.attr( 'href' );
				}
			},
			error: () => {
				// Redirect to settings page on error
				window.location = $button.attr( 'href' );
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

		// Initialize the webhook registration trigger in admin notices
		$( document ).on( 'click', selectors.registerWebhookTrigger, obj.registerWebhookFromNotice );

		// Initialize the fix webhook button in notices
		$( document ).on( 'click', selectors.fixWebhookButton, obj.handleFixWebhookClick );
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
