import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	registerAction,
	RESERVATIONS_DELETED,
	SEAT_TYPES_UPDATED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
} from '@tec/tickets/seating/service/api';
import {
	handleReservationsDeleted,
	handleReservationsUpdatedFollowingSeatTypes,
	handleSeatTypesUpdated,
} from '../action-handlers';

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

	registerAction(RESERVATIONS_DELETED, (data) =>
		handleReservationsDeleted(data?.ids || [])
	);

	registerAction(SEAT_TYPES_UPDATED, (data) =>
		handleSeatTypesUpdated(data?.seatTypes || [])
	);

	registerAction(RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES, (data) =>
		handleReservationsUpdatedFollowingSeatTypes(data?.updated || {})
	);

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
