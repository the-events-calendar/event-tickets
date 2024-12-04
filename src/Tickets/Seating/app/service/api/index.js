// Get the service base URL without the trailing slash.
import {associatedEventsUrl, getBaseUrl} from './localized-data.js';
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
	OUTBOUND_REMOVE_RESERVATIONS,
	OUTBOUND_EVENT_ATTENDEES,
	OUTBOUND_ATTENDEE_UPDATE,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED,
	SEAT_TYPES_UPDATED,
	SEAT_TYPE_DELETED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	GO_TO_ASSOCIATED_EVENTS,
	RESERVATION_UPDATED,
	RESERVATION_CREATED,
	INBOUND_SET_ELEMENT_HEIGHT,
} from './service-actions.js';

/**
 * @type {[string, Function, MessageEvent][]}
 */
let handlerQueue = [];

/**
 * Posts a message to the service iframe.
 *
 * @since 5.16.0
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
			data: data || null,
		},
		getBaseUrl()
	);
}

/**
 * Calls the next wrapper handler in the queue.
 *
 * A "wrapper handler" is a handler that will execute its own code and call the next handler
 * in the queue or, if thenable, will resolve and then call the next handler.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
function callNextHandler() {
	if (handlerQueue.length === 0) {
		return;
	}

	const [action, handler, event] = handlerQueue[0];
	const wrappedHandler = wrapHandlerForQueue(handler);

	wrappedHandler(event.data.data);
}

/**
 * Wraps a handler in a function that will call the next handler in the queue.
 *
 * Since all functions can be awaited, the wrapper will treat all functions as async functions.
 *
 * @since 5.16.0
 *
 * @param {Function} handler The handler to wrap.
 *
 * @return {Function} The wrapped handler.
 */
function wrapHandlerForQueue(handler) {
	return async (data) => {
		await handler(data);
		// Remove the first handler, this, from the queue.
		handlerQueue.shift();
		callNextHandler();
	};
}

/**
 * The function used to handle the messages received from the service.
 *
 * All messages that not conform to the expected format, or not contain the
 * required data, will be ignored.
 * Handlers are called based on the `state.actionsMap` map that is controlled
 * using the `takeAction` and `takeEveryAction` functions.
 *
 * @since 5.16.0
 *
 * @param {MessageEvent} event The message event received from the service.
 */
export function catchMessage(event) {
	if (
		!(
			event.origin === getBaseUrl() &&
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

	const handler = getHandlerForAction(action);
	handlerQueue.push([action, handler, event]);

	if (handlerQueue.length > 1) {
		// The handler will have to wait for the previous ones to finish.
		return;
	}

	// Immediately call the handler.
	callNextHandler();
}

/**
 * Listens for service messages.
 *
 * @since 5.16.0
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
 * @since 5.16.0
 *
 * @param {HTMLIFrameElement} iframe The iframe to establish the connection with the service.
 *
 * @return {Promise<void>} A promise that will be resolved when the connection is established.
 */
export async function establishReadiness(iframe) {
	// Before setting the iframe source, start listening for messages from the service.
	startListeningForServiceMessages(iframe);

	let promiseReject;

	// Build a promise that will resolve when the Service sends the ready message.
	const promise = new Promise((resolve, reject) => {
		promiseReject = reject;

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

	// Seat a 3s timeout to reject the promise if the connection is not established.
	const timeoutId = setTimeout(() => {
		promiseReject(new Error('Connection to service timed out'));
	}, 3000);

	// Finally start loading the service in the iframe and wait for its ready message.
	iframe.src = iframe.dataset.src;

	return promise;
}

/**
 * Returns the handler queue for the service.
 *
 * @since 5.16.0
 *
 * @return {Object<string, Function>} The handler queue for the service.
 */
export function getHandlerQueue() {
	return handlerQueue;
}

/**
 * Empties the handler queue.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function emptyHandlerQueue() {
	handlerQueue = [];
}

/**
 * Returns the associated events URL for the given layout ID.
 *
 * @since 5.16.0
 *
 * @param {string} layoutId The layout ID.
 *
 * @return {string} The associated events URL for the given layout ID.
 */
export function getAssociatedEventsUrl( layoutId ) {
	return layoutId ? `${associatedEventsUrl}&layout=${layoutId}` : associatedEventsUrl;
}

// Re-export some functions from the state module.
export {
	INBOUND_APP_READY,
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEATS_SELECTED,
	INBOUND_SET_ELEMENT_HEIGHT,
	OUTBOUND_EVENT_ATTENDEES,
	OUTBOUND_HOST_READY,
	OUTBOUND_REMOVE_RESERVATIONS,
	OUTBOUND_SEAT_TYPE_TICKETS,
	OUTBOUND_ATTENDEE_UPDATE,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	SEAT_TYPES_UPDATED,
	SEAT_TYPE_DELETED,
	RESERVATION_UPDATED,
	RESERVATION_CREATED,
	getHandlerForAction,
	getRegisteredActions,
	getToken,
	registerAction,
	removeAction,
};

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.service = window.tec.tickets.seating.service || {};
window.tec.tickets.seating.service.api = {
	...(window.tec.tickets.seating.service.api || {}),
	INBOUND_APP_READY,
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEATS_SELECTED,
	INBOUND_SET_ELEMENT_HEIGHT,
	OUTBOUND_EVENT_ATTENDEES,
	OUTBOUND_HOST_READY,
	OUTBOUND_REMOVE_RESERVATIONS,
	OUTBOUND_SEAT_TYPE_TICKETS,
	OUTBOUND_ATTENDEE_UPDATE,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	SEAT_TYPES_UPDATED,
	SEAT_TYPE_DELETED,
	GO_TO_ASSOCIATED_EVENTS,
	RESERVATION_UPDATED,
	RESERVATION_CREATED,
	establishReadiness,
	getHandlerForAction,
	getHandlerQueue,
	getRegisteredActions,
	getToken,
	registerAction,
	removeAction,
	sendPostMessage,
	startListeningForServiceMessages,
	getAssociatedEventsUrl,
};
