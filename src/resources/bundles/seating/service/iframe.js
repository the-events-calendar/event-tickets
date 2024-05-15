import { notifyUserOfError } from './notices';
import { establishReadiness, onEveryAction } from './service-api';

const { _x } = wp.i18n;

/**
 * The default iframe message handler.
 *
 * @param {MessageEvent} event The event that triggered the message.
 */
function catchMessage(event) {
	console.log('Message received from service:', event);
}

/**
 * Initializes the iframe, by establishing readiness and listening for messages.
 *
 * @param {Element} iframe The iframe element to initialize.
 * @return {Promise<boolean>} A promise that resolves to true if the iframe is ready to communicate with the service.
 */
async function init(iframe) {
	if (!iframe) {
		return false;
	}

	const container = iframe.closest('.tec-tickets-seating__iframe-container');

	if (!container) {
		return false;
	}

	const token = container.dataset.token;

	if (!token) {
		const defaultMessage = _x(
			'Ephemeral token not found in iframe element.',
			'Error message',
			'event-tickets'
		);
		const error = container.dataset.error || defaultMessage;
		notifyUserOfError(error);

		return false;
	}

	// Wait for the iframe readyness to be established.
	await establishReadiness(iframe);

	// After the iframe is initialized, we can listen for messages using the default handler
	onEveryAction(iframe, catchMessage);

	return true;
}

/**
 * Initializes the iframes in the document.
 *
 * @return {Promise<Element[]>} A promise that resolves to a set of initialized iframe elements.
 */
export async function iFrameInit() {
	const iframes = document.querySelectorAll(
		'.tec-tickets-seating__iframe-container iframe'
	);
	const initialized = [];

	for (const iframe of iframes) {
		if ((await init(iframe)) !== false) {
			initialized.push(iframe);
		}
	}

	return initialized;
}

window.tec = window.tec || {};
window.tec.seating = window.tec.seating || {};
window.tec.seating.iframe = {
	...(window.tec.seating.iframe || {}),
	iFrameInit,
};
