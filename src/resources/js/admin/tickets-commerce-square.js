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

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 */
	const selectors = {
		connectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-connect-square',
		disconnectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square',
		reconnectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-reconnect-square',
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

		// Show loading state
		button.classList.add( 'loading' );
		button.innerText = obj.i18n.connecting;

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_connect',
				_wpnonce: obj.i18n.connectNonce,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success && response.data.url ) {
				// Redirect to the Square authorization page
				window.location.href = response.data.url;
			} else {
				// Show error message
				alert( obj.i18n.connectError );
				button.classList.remove( 'loading' );
				button.innerText = obj.i18n.connect;
			}
		})
		.catch( () => {
			// Show error message
			alert( obj.i18n.connectError );
			button.classList.remove( 'loading' );
			button.innerText = obj.i18n.connect;
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

		// Show loading state
		button.classList.add( 'loading' );
		button.innerText = obj.i18n.connecting;

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
				_wpnonce: obj.i18n.connectNonce,
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
				alert( obj.i18n.connectError );
				button.classList.remove( 'loading' );
				button.innerText = obj.i18n.reconnect;
			}
		})
		.catch( () => {
			// Show error message
			alert( obj.i18n.connectError );
			button.classList.remove( 'loading' );
			button.innerText = obj.i18n.reconnect;
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

		// Confirm disconnection
		if ( ! confirm( obj.i18n.disconnectConfirm ) ) {
			return;
		}

		const button = event.currentTarget;

		// Show loading state
		button.classList.add( 'loading' );

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_disconnect',
				_wpnonce: button.dataset.nonce,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success ) {
				// Reload the page to show updated state
				window.location.reload();
			} else {
				// Show error message
				alert( obj.i18n.disconnectError );
				button.classList.remove( 'loading' );
			}
		})
		.catch( () => {
			// Show error message
			alert( obj.i18n.disconnectError );
			button.classList.remove( 'loading' );
		});
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
