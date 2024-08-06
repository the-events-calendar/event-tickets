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
	OUTBOUND_ATTENDEE_UPDATE,
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
 * @property {number} purchaser.name                The attendee name.
 * @property {number} purchaser.associatedAttendees The number of attendees associated with the purchaser.
 * @property {number} ticketId                      The attendee ticket ID.
 * @property {string} seatTypeId                    The attendee seat type UUID.
 * @property {string} seatLabel                     The attendee seat label.
 * @property {string} reservationId                 The attendee reservation UUID.
 */

/**
 * @typedef {Object} EventAttendeesBatch
 * @property {number}               currentBatch The current batch number.
 * @property {number}               totalBatches The total number of batches.
 * @property {number|false}         nextBatch    The next batch number or `false` if there are no more batches.
 * @property {SeatReportAttendee[]} attendees    The attendees data.
 */

/**
 * Fetches attendees for a given post ID.
 *
 * @since TBD
 *
 * @param {number} currentBatch The batch number to fetch.
 *
 * @return {Promise<EventAttendeesBatch>} A promise that will be resolved with the attendees.
 */
export async function fetchAttendees(currentBatch) {
	currentBatch = currentBatch || 1;
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('postId', postId);
	url.searchParams.set('action', ACTION_FETCH_ATTENDEES);
	url.searchParams.set('currentBatch', currentBatch);
	const response = await fetch(url.toString(), {
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

	return {
		attendees: json?.data?.attendees || [],
		totalBatches: json?.data?.totalBatches || 1,
		currentBatch: json?.data?.currentBatch || 1,
		nextBatch: json?.data?.nextBatch || false,
	};
}

/**
 * Recursively fetches attendees in batches and sends them to the service via the iframe.
 *
 * @since TBD
 *
 * @param {HTMLElement} iframe         The service iframe element to send messages to.
 * @param {number}      currentBatch   The batch number to fetch.
 * @param {Function}    resolve        The function to call when the attendees have been sent.
 * @param {number}      totalAttendees The running total of Attendees sent so far.
 *
 * @return {Promise<void>} A promise that will resolve the total number of Attendees sent.
 */
export async function fetchAndSendAttendeeBatch(
	iframe,
	currentBatch,
	resolve,
	totalAttendees
) {
	return fetchAttendees(currentBatch).then((batch) => {
		const nextBatch = batch?.nextBatch || false;
		const totalBatches = batch?.totalBatches || 1;
		const attendeeData = {
			totalBatches,
			currentBatch,
			attendees: batch?.attendees || [],
		};
		sendPostMessage(iframe, OUTBOUND_EVENT_ATTENDEES, attendeeData);
		const updatedTotalAttendees =
			totalAttendees + attendeeData.attendees.length;

		if (!nextBatch) {
			resolve(updatedTotalAttendees);
			return;
		}

		return fetchAndSendAttendeeBatch(
			iframe,
			nextBatch,
			resolve,
			updatedTotalAttendees
		);
	});
}

/**
 * Fetches the attendees in batches and sends them to the service via the iframe.
 *
 * @since TBD
 *
 * @param {HTMLElement} iframe The service iframe element to send messages to.
 *
 * @return {Promise<number>} A promise that will be resolved to the total number of Attendees sent.
 */
export async function sendAttendeesToService(iframe) {
	return new Promise((resolve) => {
		fetchAndSendAttendeeBatch(iframe, 1, resolve, 0);
	});
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
		await sendAttendeesToService(iframe);
	});
}

/**
 * Initializes iframe and the communication with the service.
 *
 * @since TBD
 *
 * @param {HTMLDocument|null} dom The document to use to search for the iframe element.
 *
 * @return {Promise<void>} A promise that resolves when the iframe is initialized.
 */
export async function init(dom) {
	dom = dom || document;

	const iframe = getIframeElement(dom);

	if (!iframe) {
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);
	await initServiceIframe(iframe);
}

onReady(() => {
	init(document);
});
