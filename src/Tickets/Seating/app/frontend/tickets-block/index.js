import './style.pcss';
import { getIframeElement, initServiceIframe } from '@tec/tickets/seating/iframe';
import {
	sendPostMessage,
	removeAction,
	OUTBOUND_SEAT_TYPE_TICKETS,
	INBOUND_APP_READY_FOR_DATA,
} from '@tec/tickets/seating/service';
import { registerAction, getRegisteredActions } from '../../service/service-api';

const { objectName, seatTypeMap } =
	window?.tec?.seating?.frontend?.ticketsBlock;

function registerActions(iframe) {
	// When the service is ready for data, send the seat type map to the iframe.
	registerAction(INBOUND_APP_READY_FOR_DATA, () => {
		removeAction(INBOUND_APP_READY_FOR_DATA);
		sendPostMessage(iframe, OUTBOUND_SEAT_TYPE_TICKETS, seatTypeMap);
	});

	console.log('actions', getRegisteredActions());
}

async function bootstrapIframe() {
	const iframe = getIframeElement();

	if (!iframe) {
		console.error('Iframe element not found.');
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);

	await initServiceIframe(iframe);
}

function initModal(modalElement) {
	modalElement.on('show', bootstrapIframe);
}

/**
 * Waits for the modal element to be present in the DOM.
 *
 * @return {Promise<Element>} A promise that resolves to the modal element.
 */
async function waitForModalElement() {
	return new Promise((resolve) => {
		const check = () => {
			if (window[objectName]) {
				resolve(window[objectName]);
			}
			setTimeout(check, 50);
		};

		check();
	});
}

waitForModalElement().then((modalElement) => {
	initModal(modalElement);
});
