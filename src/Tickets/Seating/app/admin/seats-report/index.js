import './style.pcss';
import { initServiceIframe, getIframeElement } from '@tec/tickets/seating/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	INBOUND_APP_READY_FOR_DATA,
	OUTBOUND_SEAT_TYPE_TICKETS,
	removeAction,
	registerAction,
	sendPostMessage,
} from '@tec/tickets/seating/service';
const { seatTypeMap } = window?.tec?.tickets?.seating?.seatsReport?.data;


/**
 * Registers the handlers for the messages received from the service.
 *
 * @since TBD
 *
 * @param {HTMLElement} iframe The service iframe element to listen to.
 */
function registerActions(iframe) {
	// When the service is ready for data, send the seat type map to the iframe.
	registerAction(INBOUND_APP_READY_FOR_DATA, () => {
		removeAction(INBOUND_APP_READY_FOR_DATA);
		sendPostMessage(iframe, OUTBOUND_SEAT_TYPE_TICKETS, seatTypeMap);
	});
}

onReady( async () => {
	const iframe = getIframeElement();
	if (!iframe) {
		console.error('Iframe element not found.');
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);
	await initServiceIframe(iframe);
})