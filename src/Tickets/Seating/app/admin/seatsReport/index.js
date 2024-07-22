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
import {
	ajaxUrl,
	ajaxNonce,
	ACTION_FETCH_ATTENDEES,
} from '@tec/tickets/seating/ajax';
import { localizedData } from './localized-data';

const { seatTypeMap, postId } = localizedData;

/**
 * @typedef {Object} SeatReportAttendee
 * @property {number} id                            The attendee ID.
 * @property {string} name                          The attendee name.
 * @property {Object} purchaser                     The attendee purchaser data.
 * @property {number} purchaser.id                  The attendee purchaser ID.
 * @property {number} purchaser.associatedAttendees The number of attendees associated with the purchaser.
 * @property {number} ticketId                      The attendee ticket ID.
 * @property {string} seatTypeId                    The attendee seat type UUID.
 * @property {string} seatLabel                     The attendee seat label.
 * @property {string} reservationId                 The attendee reservation UUID.
 */

/**
 * Fetches attendees for a given post ID.
 *
 * @since TBD
 *
 * @return {Promise<SeatReportAttendee[]>} A promise that will be resolved with the attendees.
 */
export async function fetchAttendees() {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('postId', postId);
	url.searchParams.set('action', ACTION_FETCH_ATTENDEES);
	const response = await fetch(url.toString(), {
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
