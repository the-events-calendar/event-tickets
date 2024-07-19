import './style.pcss';
import {
	initServiceIframe,
	getIframeElement,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	INBOUND_APP_READY_FOR_DATA,
	OUTBOUND_SEAT_TYPE_TICKETS,
	OUTBOUND_EVENT_ATTENDEES,
	removeAction,
	registerAction,
	sendPostMessage,
} from '@tec/tickets/seating/service/api';
import { ajaxUrl, ajaxNonce } from '@tec/tickets/seating/service';
import { localizedData, fetchAttendeesAjaxUrl } from './localized-data';

const { seatTypeMap, postId } = localizedData;

async function fetchAttendees() {
	const response = await fetch(fetchAttendeesAjaxUrl, {
		method: 'POST',
		headers: {
			Accept: 'application/json',
		},
	});

	const json = await response.json();

	if (response.status !== 200) {
		throw new Error(
			`Failed to fetch attendees for post ID ${postId}. Status: ${response.status} - ${json?.data?.error}`
		);
	}

	return json?.data || [];
}
/**
 * Registers the handlers for the messages received from the service.
 *
 * @since TBD
 *
 * @param {HTMLElement} iframe The service iframe element to listen to.
 */
function registerActions(iframe) {
	// When the service is ready for data, send the seat type map to the iframe.
	registerAction(INBOUND_APP_READY_FOR_DATA, async () => {
		removeAction(INBOUND_APP_READY_FOR_DATA);

		sendPostMessage(iframe, OUTBOUND_SEAT_TYPE_TICKETS, seatTypeMap);

		const attendeeData = await fetchAttendees();
		sendPostMessage(
			iframe,
			OUTBOUND_EVENT_ATTENDEES,
			attendeeData?.attendees
		);
	});
}

onReady(async () => {
	const iframe = getIframeElement();
	if (!iframe) {
		console.error('Iframe element not found.');
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);
	await initServiceIframe(iframe);
});
