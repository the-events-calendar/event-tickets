/**
 * Tickets Commerce Square integration JavaScript.
 *
 * @since TBD
 */

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.commerce = window.tec.tickets.commerce || {};

/**
 * Tickets Commerce Square integration object.
 *
 * @since TBD
 */
window.tec.tickets.commerce.square = window.tec.tickets.commerce.square || {};

/**
 * Initializes Tickets Commerce Square integration.
 *
 * @since TBD
 *
 * @param {Document} document The document object.
 * @return {void}
 */
( ( document, obj ) => {
	'use strict';

	const { __ } = wp.i18n;

	/**
	 * Default strings used in the module.
	 *
	 * @since TBD
	 */
	const strings = {
		connect: __( 'Connect with Square', 'event-tickets' ),
		connecting: __( 'Connecting...', 'event-tickets' ),
		reconnect: __( 'Reconnect Account', 'event-tickets' ),
		connectError: __( 'There was an error connecting to Square. Please try again.', 'event-tickets' ),
		disconnectConfirm: __( 'Are you sure you want to disconnect from Square?', 'event-tickets' ),
		disconnectError: __( 'There was an error disconnecting from Square. Please try again.', 'event-tickets' ),
		disconnecting: __( 'Disconnecting...', 'event-tickets' ),
		disconnect: __( 'Disconnect from Square', 'event-tickets' ),
	};

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 */
	const selectors = {
		connectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-connect-square',
		disconnectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square',
		reconnectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-reconnect-square',
		container: '#tec-tickets__admin-settings-tickets-commerce-gateway-square-container',
		disconnectDialog: '#tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square-dialog',
		disconnectCancel: '.tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-cancel',
		disconnectConfirm: '.tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-confirm',
	};

	/**
	 * Get strings from data attributes on the container.
	 *
	 * @since TBD
	 *
	 * @return {Object} The strings data object.
	 */
	const getStrings = () => {
		const container = document.querySelector(selectors.container);

		if (!container) {
			return {
				...strings,
				connectNonce: '',
			};
		}

		return {
			connect: container.dataset.connect || strings.connect,
			connecting: container.dataset.connecting || strings.connecting,
			reconnect: container.dataset.reconnect || strings.reconnect,
			connectError: container.dataset.connectError || strings.connectError,
			disconnectConfirm: container.dataset.disconnectConfirm || strings.disconnectConfirm,
			disconnectError: container.dataset.disconnectError || strings.disconnectError,
			connectNonce: container.dataset.connectNonce || '',
		};
	};

	/**
	 * Handle connect button click.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	const handleConnectClick = ( event ) => {
		event.preventDefault();

		const button = event.currentTarget;
		const strings = getStrings();

		// Show loading state
		button.classList.add( 'loading' );
		button.innerText = strings.connecting;

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_connect',
				_wpnonce: obj.localized.connectNonce,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success && response.data.url ) {
				// Redirect to the Square authorization page
				window.location.href = response.data.url;
			} else {
				// Show error message
				alert( strings.connectError );
				button.classList.remove( 'loading' );
				button.innerText = strings.connect;
			}
		})
		.catch( () => {
			// Show error message
			alert( strings.connectError );
			button.classList.remove( 'loading' );
			button.innerText = strings.connect;
		});
	};

	/**
	 * Handle reconnect button click.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	const handleReconnectClick = ( event ) => {
		event.preventDefault();

		const button = event.currentTarget;
		const strings = getStrings();

		// Show loading state
		button.classList.add( 'loading' );
		button.innerText = strings.connecting;

		// Get required scopes if available
		const requiredScopes = button.dataset.requiredScopes || '';

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_connect',
				_wpnonce: strings.connectNonce,
				scopes: requiredScopes,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success && response.data.url ) {
				// Redirect to the Square authorization page
				window.location.href = response.data.url;
			} else {
				// Show error message
				alert( strings.connectError );
				button.classList.remove( 'loading' );
				button.innerText = strings.reconnect;
			}
		})
		.catch( () => {
			// Show error message
			alert( strings.connectError );
			button.classList.remove( 'loading' );
			button.innerText = strings.reconnect;
		});
	};

	/**
	 * Handle disconnect button click.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event.
	 * @return {void}
	 */
	const handleDisconnectClick = ( event ) => {
		event.preventDefault();

		// Show the custom dialog
		const dialog = document.querySelector( selectors.disconnectDialog );
		if ( dialog ) {
			dialog.style.display = 'flex';
		}
	};

	/**
	 * Process the disconnect request
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const processDisconnect = () => {
		const strings = getStrings();
		const disconnectButton = document.querySelector( selectors.disconnectButton );
		const dialog = document.querySelector( selectors.disconnectDialog );

		// Hide the dialog
		if ( dialog ) {
			dialog.style.display = 'none';
		}

		// Show loading state
		disconnectButton.classList.add( 'loading' );
		disconnectButton.innerText = strings.disconnecting;
		disconnectButton.disabled = true;

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_disconnect',
				_wpnonce: disconnectButton.dataset.nonce,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success ) {
				// Reload the page to show updated state
				window.location.reload();
			} else {
				// Show error message
				alert( strings.disconnectError );
				disconnectButton.classList.remove( 'loading' );
				disconnectButton.innerText = strings.disconnect;
				disconnectButton.disabled = false;
			}
		})
		.catch( () => {
			// Show error message
			alert( strings.disconnectError );
			disconnectButton.classList.remove( 'loading' );
			disconnectButton.innerText = strings.disconnect;
			disconnectButton.disabled = false;
		});
	};

	/**
	 * Cancel disconnect request
	 *
	 * @since TBD
	 *
	 * @param {Event} event The click event
	 * @return {void}
	 */
	const cancelDisconnect = ( event ) => {
		event.preventDefault();

		const dialog = document.querySelector( selectors.disconnectDialog );
		if ( dialog ) {
			dialog.style.display = 'none';
		}
	};

	/**
	 * Bind events for Square integration.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const bindEvents = () => {
		const connectButton = document.querySelector( selectors.connectButton );
		if ( connectButton ) {
			connectButton.addEventListener( 'click', handleConnectClick );
		}

		const disconnectButton = document.querySelector( selectors.disconnectButton );
		if ( disconnectButton ) {
			disconnectButton.addEventListener( 'click', handleDisconnectClick );
		}

		const reconnectButton = document.querySelector( selectors.reconnectButton );
		if ( reconnectButton ) {
			reconnectButton.addEventListener( 'click', handleReconnectClick );
		}

		// Dialog buttons
		const cancelButton = document.querySelector( selectors.disconnectCancel );
		if ( cancelButton ) {
			cancelButton.addEventListener( 'click', cancelDisconnect );
		}

		const confirmButton = document.querySelector( selectors.disconnectConfirm );
		if ( confirmButton ) {
			confirmButton.addEventListener( 'click', processDisconnect );
		}

		// Close dialog when clicking outside of it
		const dialog = document.querySelector( selectors.disconnectDialog );
		if ( dialog ) {
			dialog.addEventListener( 'click', ( event ) => {
				if ( event.target === dialog ) {
					cancelDisconnect( event );
				}
			} );
		}
	};

	/**
	 * Initialize Square integration.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const init = () => {
		bindEvents();
	};

	// When the DOM is ready, initialize
	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}

} )( document, window.tec.tickets.commerce.square );
