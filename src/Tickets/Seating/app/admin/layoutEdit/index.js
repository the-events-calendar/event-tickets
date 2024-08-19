import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady, redirectTo } from '@tec/tickets/seating/utils';
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
 * Go to associated events.
 *
 * @since TBD
 *
 * @param data
 */
export function goToAssociatedEvents( data ) {
	if ( data.layoutId ) {
		redirectTo( getAssociatedEventsUrl( data.layoutId ), true );
	}
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

	registerAction(RESERVATIONS_DELETED, handleReservationsDeleted);

	registerAction(SEAT_TYPES_UPDATED, handleSeatTypesUpdated);

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
