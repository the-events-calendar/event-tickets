// Get the service base URL without the trailing slash.
const baseUrl = tec.tickets.seating.service.baseUrl.replace(/\/$/, '');
tec.tickets.seating.service.state =  tec.tickets.seating.service.state || {
	ready: false,
	establishingReadiness: false,
	actionsMap: {
		default: defaultMessageHandler,
	},
	token: null,
};
const state = tec.tickets.seating.service.state;

export const INBOUND_APP_READY = 'app_postmessage_ready';
export const INBOUND_APP_READY_FOR_DATA = 'app_postmessage_ready_for_data';
export const OUTBOUND_HOST_READY = 'host_postmessage_ready';
export const OUTBOUND_SEAT_TYPE_TICKETS = 'host_postmessage_seat_type_tickets';
export const INBOUND_SEATS_SELECTED = 'app_postmessage_seats_selected';

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
			event.data.token === state.token
		)
	) {
		return;
	}

	const action = event.data.action;

	if (!action) {
		console.error('No action found in message');
		return;
	}

	const handler = state.actionsMap[action]
		? state.actionsMap[action]
		: defaultMessageHandler;

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

	state.token = token;

	window.addEventListener('message', catchMessage);
}

/**
 * Sets the callback for a specific action to the callback.
 *
 * @since TBD
 *
 * @param {string|string[]} action   The action, or actions, to set the callback for.
 * @param {Function}        callback The callback to set.
 *
 * @return {void}
 */
export function registerAction(action, callback) {
	state.actionsMap[action] = callback;
}

/**
 * The default message handler that will be called when a message is received from the service.
 *
 * @since TBD
 *
 * @param {MessageEvent} event The message event received from the service.
 *
 * @return {void}
 */
function defaultMessageHandler(event) {
	console.debug('Message received from service', event);
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

			state.ready = true;
			state.establishingReadiness = false;

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

/**
 * Removes the listener for a specific action.
 *
 * @since TBD
 *
 * @param {string} action The action to remove the listener for.
 */
export function removeAction(action) {
	delete state.actionsMap[action];
}

export function getRegisteredActions() {
	return state.actionsMap;
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
