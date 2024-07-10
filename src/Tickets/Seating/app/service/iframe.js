import { notifyUserOfError } from '@tec/tickets/seating/service/notices';
import { establishReadiness } from './api';

const { _x } = wp.i18n;

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

	// Wait for the iframe readiness to be established.
	await establishReadiness(iframe);

	return true;
}

/**
 * Returns the first iframe element in the document.
 *
 * @return {Element|null} The first iframe element in the document, or `null` if there is none.
 */
export function getIframeElement() {
	return document.querySelector(
		'.tec-tickets-seating__iframe-container iframe'
	);
}

/**
 * Initializes the service iframe document.
 *
 * @param {Element} iframe The iframe element to initialize.
 *
 * @return {Promise<Element>} A promise that resolves to the initialized iframe element.
 */
export async function initServiceIframe(iframe) {
	await init(iframe);

	return iframe;
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.service = window.tec.tickets.seating.service || {};
window.tec.tickets.seating.service.iframe = {
	...(window.tec.tickets.seating.service.iframe || {}),
	getIframeElement,
	initServiceIframe,
};
