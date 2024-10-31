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
	RESERVATIONS_DELETED,
	RESERVATION_UPDATED,
	RESERVATION_CREATED,
	removeAction,
	registerAction,
	sendPostMessage,
} from '@tec/tickets/seating/service/api';
import {
	ajaxUrl,
	ajaxNonce,
	ACTION_FETCH_ATTENDEES,
	ACTION_RESERVATION_CREATED,
	ACTION_RESERVATION_UPDATED,
} from '@tec/tickets/seating/ajax';
import { localizedData } from './localized-data';
import { handleReservationsDeleted } from '../action-handlers';

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
 * @since 5.16.0
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
 * @since 5.16.0
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
 * @since 5.16.0
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
 * @typedef {Object} ReservationUpdatedProps
 * @property {string}  reservationId        The reservation UUID.
 * @property {string}  attendeeId           The Attendee ID.
 * @property {string}  seatTypeId           The seat type UUID.A
 * @property {string}  seatLabel            The seat label.
 * @property {string}  seatColor            The seat color.
 * @property {boolean} sendUpdateToAttendee Whether to send the updated Attendee to the service
 *
 * @typedef {ReservationUpdatedProps} ReservationCreatedProps
 * @property {number}  ticketId             The ticket ID.
 */

/**
 * Updates the Attendee with the new reservation data.
 *
 * @since 5.16.0
 *
 * @param {ReservationCreatedProps|ReservationUpdatedProps} props The update/create properties.
 *
 * @return {SeatReportAttendee} The updated Attendee data.
 */
export async function updateAttendeeReservation(props) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	const action = props.ticketId
		? ACTION_RESERVATION_CREATED
		: ACTION_RESERVATION_UPDATED;
	url.searchParams.set('action', action);
	url.searchParams.set('postId', postId);
	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(props),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	if (!json.data) {
		return false;
	}

	return json.data;
}

/**
 * Handles the action to create an Attendee reservation.
 *
 * @since 5.16.0
 *
 * @param {HTMLElement}             iframe The service iframe element to send messages to.
 * @param {ReservationCreatedProps} props  The action properties.
 *
 * @return {Promise<boolean>} A promise that will resolve to `true` if the Attendee reservation was created, `false` otherwise.
 */
export async function handleReservationCreated(iframe, props) {
	const updatedAttendee = await updateAttendeeReservation(props);

	if (!updatedAttendee) {
		return false;
	}

	sendPostMessage(iframe, OUTBOUND_ATTENDEE_UPDATE, {
		attendee: updatedAttendee,
	});

	return true;
}

/**
 * Handles the action to update an Attendee reservation.
 *
 * @since 5.16.0
 *
 * @param {HTMLElement}             iframe The service iframe element to send messages to.
 * @param {ReservationUpdatedProps} props  The action properties.
 *
 * @return {Promise<boolean>} A promise that will resolve to `true` if the Attendee reservation was updated, `false` otherwise.
 */
export async function handleReservationUpdated(iframe, props) {
	const updatedAttendee = await updateAttendeeReservation(props);

	if (!updatedAttendee) {
		return false;
	}

	sendPostMessage(iframe, OUTBOUND_ATTENDEE_UPDATE, {
		attendee: updatedAttendee,
	});

	return true;
}

/**
 * Registers the handlers for the messages received from the service.
 *
 * @since 5.16.0
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

	registerAction(RESERVATION_CREATED, (props) =>
		handleReservationCreated(iframe, props)
	);

	registerAction(RESERVATION_UPDATED, (props) =>
		handleReservationUpdated(iframe, props)
	);

	registerAction(RESERVATIONS_DELETED, handleReservationsDeleted );
}

/**
 * Initializes iframe and the communication with the service.
 *
 * @since 5.16.0
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
