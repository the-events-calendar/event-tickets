/**
 * Tickets Commerce Square integration JavaScript.
 *
 * @since TBD
 */

window.tribe = window.tribe || {};
tribe.tickets = tribe.tickets || {};
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Tickets Commerce Square integration object.
 *
 * @since TBD
 */
tribe.tickets.commerce.square = {};

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

	tribe.tickets.commerce.square.i18n = window.tribe_tickets_commerce_square_strings;

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 */
	const selectors = {
		connectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-connect-square',
		disconnectButton: '#tec-tickets__admin-settings-tickets-commerce-gateway-disconnect-square',
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
		button.innerText = tribe.tickets.commerce.square.i18n.connecting;

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'tec_tickets_commerce_square_connect',
				_wpnonce: tribe.tickets.commerce.square.i18n.connectNonce,
			}),
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success && response.data.url ) {
				// Redirect to the Square authorization page
				window.location.href = response.data.url;
			} else {
				// Show error message
				alert( tribe.tickets.commerce.square.i18n.connectError );
				button.classList.remove( 'loading' );
				button.innerText = tribe.tickets.commerce.square.i18n.connect;
			}
		})
		.catch( () => {
			// Show error message
			alert( tribe.tickets.commerce.square.i18n.connectError );
			button.classList.remove( 'loading' );
			button.innerText = tribe.tickets.commerce.square.i18n.connect;
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
		if ( ! confirm( tribe.tickets.commerce.square.i18n.disconnectConfirm ) ) {
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
				alert( tribe.tickets.commerce.square.i18n.disconnectError );
				button.classList.remove( 'loading' );
			}
		})
		.catch( () => {
			// Show error message
			alert( tribe.tickets.commerce.square.i18n.disconnectError );
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

} )( document, tribe.tickets.commerce.square );
