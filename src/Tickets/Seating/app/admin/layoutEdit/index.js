import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	getAssociatedEventsUrl,
	registerAction,
	RESERVATIONS_DELETED,
	SEAT_TYPES_UPDATED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	GO_TO_ASSOCIATED_EVENTS,
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

	registerAction(RESERVATIONS_DELETED, handleReservationsDeleted);

	registerAction(SEAT_TYPES_UPDATED, handleSeatTypesUpdated);

	registerAction(
		RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
		handleReservationsUpdatedFollowingSeatTypes
	);

	registerAction(GO_TO_ASSOCIATED_EVENTS, ( data ) => {
		window.location.href = getAssociatedEventsUrl(data.layoutId);
	} );

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});