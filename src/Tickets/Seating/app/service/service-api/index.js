// Get the service base URL without the trailing slash.
import { baseUrl } from './externals.js';
import {
	setIsReady,
	setEstablishingReadiness,
	registerAction,
	removeAction,
	getRegisteredActions,
	getToken,
	setToken,
	getHandlerForAction,
} from './state.js';
import {
	INBOUND_APP_READY,
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEATS_SELECTED,
	OUTBOUND_HOST_READY,
	OUTBOUND_SEAT_TYPE_TICKETS,
} from './service-actions.js';
import { defaultMessageHandler } from './message-handlers';

/**
 * Posts a message to the service iframe.
 *
 * @since TBD
 *
 * @param {HTMLIFrameElement} iframe The iframe to post the message to.
 * @param {string}            action The message action.
 * @param {*}                 data   The message data.
 *
 * @return {void}
 */
export function sendPostMessage(iframe, action, data) {
	const token = iframe.closest('[data-token]').dataset.token;

	if (!token) {
		console.error('No token found in iframe element');
		return;
	}

	iframe.contentWindow.postMessage(
		{
			action,
			token,
			data: data || {},
		},
		baseUrl
	);
}

/**
 * The function used to handle the messages received from the service.
 *
 * All messages that not conform to the expected format, or not contain the
 * required data, will be ignored.
 * Handlers are called based on the `state.actionsMap` map that is controlled
 * using the `takeAction` and `takeEveryAction` functions.
 *
 * @since TBD
 *
 * @param {MessageEvent} event The message event received from the service.
 */
export function catchMessage(event) {
	if (
		!(
			event.origin === baseUrl &&
			event.data.token &&
			event.data.token === getToken()
		)
	) {
		return;
	}

	const action = event.data.action;

	if (!action) {
		console.error('No action found in message');
		return;
	}

	const handler = getHandlerForAction(action, defaultMessageHandler);

	handler(event.data.data);
}

/**
 * Listens for service messages.
 *
 * @since TBD
 *
 * @param {HTMLIFrameElement} iframe The iframe to listen for messages from.
 *
 * @return {void}
 */
export function startListeningForServiceMessages(iframe) {
	const tokenProvider = iframe.closest('[data-token]');

	if (!tokenProvider) {
		console.error('No token provider found in iframe element');
		return;
	}

	const token = tokenProvider.dataset.token;

	if (!token) {
		console.error('No token found in token provider element');
		return;
	}

	setToken(token);

	window.addEventListener('message', catchMessage);
}

/**
 * Starts the process of establishing the connection with the service through the iframe.
 *
 * The connection is initiated by the Service by sending a `app_postmessage_ready` message through the iframe.
 * The Site will reply with a `host_postmessage_ready` message to confirm the connection is established.
 *
 * @since TBD
 *
 * @param {HTMLIFrameElement} iframe The iframe to establish the connection with the service.
 *
 * @return {Promise<void>} A promise that will be resolved when the connection is established.
 */
export async function establishReadiness(iframe) {
	// Before setting the iframe source, start listening for messages from the service.
	startListeningForServiceMessages(iframe);

	// Build a promise that will resolve when the Service sends the ready message.
	const promise = new Promise((resolve) => {
		const acknowledge = () => {
			removeAction(INBOUND_APP_READY);

			setIsReady(true);
			setEstablishingReadiness(false);

			// Acknowledge the readiness, do not wait for a reply.
			sendPostMessage(iframe, OUTBOUND_HOST_READY);

			// Readiness is established, clear the timeout.
			clearTimeout(timeoutId);

			console.debug('Readiness established.');

			resolve();
		};

		// When the ready message from the service is received, acknowledge the readiness, resolve the promise.
		registerAction(INBOUND_APP_READY, acknowledge);
	});

	// Seat a 10s timeout to reject the promise if the connection is not established.
	const timeoutId = setTimeout(() => {
		promise.reject(new Error('Connection to service timed out'));
	}, 10000);

	// Finally start loading the service in the iframe and wait for its ready message.
	iframe.src = iframe.dataset.src;

	return promise;
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.service = {
	...(window.tec.tickets.seating.service || {}),
	INBOUND_APP_READY,
	INBOUND_APP_READY_FOR_DATA,
	OUTBOUND_HOST_READY,
	OUTBOUND_SEAT_TYPE_TICKETS,
	INBOUND_SEATS_SELECTED,
	sendPostMessage,
	startListeningForServiceMessages,
	establishReadiness,
	registerAction,
	removeAction,
	getRegisteredActions,
};
