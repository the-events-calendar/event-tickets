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
 * @since 5.16.0
 *
 * @param {HTMLDocument|null} dom The document to use to get the iframe element.
 *
 * @return {Element|null} The first iframe element in the document, or `null` if there is none.
 */
export function getIframeElement(dom) {
	dom = dom || document;

	return dom.querySelector('.tec-tickets-seating__iframe-container iframe');
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

/**
 * @typedef {Object} ResizeData
 * @property {number} height The new height.
 */

/**
 * Handles resize requests.
 *
 * @since 5.16.0
 *
 * @param {ResizeData} data The new height.
 * @param {HTMLDocument|null} dom The document to use to search for the iframe element.
 */
export function handleResize(data, dom) {
	const iframe = getIframeElement(dom);
	iframe.style.height = data.height + 'px';
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.service = window.tec.tickets.seating.service || {};
window.tec.tickets.seating.service.iframe = {
	...(window.tec.tickets.seating.service.iframe || {}),
	getIframeElement,
	initServiceIframe,
	handleResize,
};
