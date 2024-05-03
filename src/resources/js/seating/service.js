// Get the service base URL without the trailing slash.
const baseUrl = tec.seating.service.baseUrl.replace(/\/$/, '');

const ACTION_APP_POSTMESSAGE_READY = 'app_postmessage_ready';
const ACTION_HOST_POSTMESSAGE_READY = 'host_postmessage_ready';

const state = {
	ready: false, establishingReadiness: false,
	actionsMap: {},
	token: null,
};

/**
 * Posts a message to the service iframe.
 *
 * @since TBD
 *
 * @param {HTMLIFrameElement} iframe The iframe to post the message to.
 * @param {string} action The message action.
 * @param {*} data The message data.
 *
 * @return {void}
 */
function sendMessage(iframe, action, data) {
	const token = iframe.closest('[data-token]').dataset.token;

	if (!token) {
		console.error('No token found in iframe element');
		return;
	}

	iframe.contentWindow.postMessage({
		action, token, data: data || {},
	}, baseUrl);
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
function catchMessage(event) {
	if (!(
		event.origin === baseUrl
		&& event.data.token
		&& event.data.token === state.token)
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

	handler(event);
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
function listenForServiceMessages(iframe) {
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
 * @param {string|string[]} action The action, or actions, to set the callback for.
 * @param {function} callback The callback to set.
 *
 * @return {void}
 */
function onAction(action, callback) {
	const actions = Array.isArray(action) ? action : [action];
	actions.forEach((action) => {
		state.actionsMap[action] = callback;
	});
}

/**
 * Sets the callback for alla actions to the callback.
 *
 * @since TBD
 *
 * @param {function} callback The callback to set for all actions.
 *
 * @return {void}
 */
function onEveryAction(callback) {
	state.actionsMap = {'default': callback};
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
 * The connection is initiated by the Service by sendind a `host_postmessage_ready` message through the iframe.
 * The Site will reply with a `app_postmessage_ready` message to confirm the connection is established.
 *
 * @since TBD
 *
 * @param {HTMLIFrameElement} iframe The iframe to establish the connection with the service.
 *
 * @return {Promise<void>} A promise that will be resolved when the connection is established.
 */
async function establishReadiness(iframe) {
	listenForServiceMessages(iframe);

	// Replace the iframe src with the real source.
	iframe.src = iframe.dataset.src;

	return new Promise((resolve, reject) => {
		const acknowledge = () => {
			// Set a 10s timer to reject the promise if the connection is not established.
			const timeoutId = setTimeout(() => {
				reject(new Error('Connection to service timed out'));
			}, 10000);

			sendMessage(iframe, ACTION_APP_POSTMESSAGE_READY);

			// We're ready.
			state.ready = true;
			state.establishingReadiness = false;

			// All actions should be handled with the default message handler.
			onEveryAction(defaultMessageHandler);

			// Clear the timeout.
			clearTimeout(timeoutId);

			console.debug('Readiness established.');
			resolve();
		};
		onAction(ACTION_HOST_POSTMESSAGE_READY, acknowledge);
	});
}

window.tec = window.tec || {};
window.tec.seating = window.tec.seating || {};
window.tec.seating.service = {
	...(window.tec.seating.service || {}),
	sendMessage,
	listenForServiceMessages,
	establishReadiness,
	actions: {
		APP_POSTMESSAGE_READY: ACTION_APP_POSTMESSAGE_READY,
		HOST_POSTMESSAGE_READY: ACTION_HOST_POSTMESSAGE_READY,
	},
	onAction,
	onEveryAction,
};