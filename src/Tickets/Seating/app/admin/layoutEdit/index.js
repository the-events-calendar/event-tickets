import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
	handleResize
} from '@tec/tickets/seating/service/iframe';
import { onReady, redirectTo } from '@tec/tickets/seating/utils';
import {
	getAssociatedEventsUrl,
	registerAction,
	RESERVATIONS_DELETED,
	SEAT_TYPES_UPDATED,
	SEAT_TYPE_DELETED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	GO_TO_ASSOCIATED_EVENTS,
	INBOUND_SET_ELEMENT_HEIGHT,
} from '@tec/tickets/seating/service/api';
import {
	handleReservationsDeleted,
	handleReservationsUpdatedFollowingSeatTypes,
	handleSeatTypesUpdated,
	handleSeatTypeDeleted,
} from '../action-handlers';

/**
 * @typedef {Object} AssociatedEventsData
 * @property {string} layoutId The ID of the layout.
 */

/**
 * Go to associated events.
 *
 * @since 5.16.0
 *
 * @param {AssociatedEventsData} data The layout ID.
 */
export function goToAssociatedEvents( data ) {
	if ( data.layoutId ) {
		redirectTo( getAssociatedEventsUrl( data.layoutId ), true );
	}
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

	registerAction(INBOUND_SET_ELEMENT_HEIGHT, (data) => handleResize( data, dom ));

	registerAction(RESERVATIONS_DELETED, handleReservationsDeleted);

	registerAction(SEAT_TYPES_UPDATED, handleSeatTypesUpdated);

	registerAction(SEAT_TYPE_DELETED, handleSeatTypeDeleted);

	registerAction(
		RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
		handleReservationsUpdatedFollowingSeatTypes
	);

	registerAction(GO_TO_ASSOCIATED_EVENTS, goToAssociatedEvents);

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
