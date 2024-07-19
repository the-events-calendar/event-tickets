import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	registerAction,
	RESERVATIONS_DELETED,
} from '@tec/tickets/seating/service/api';

function handleReservationsDeleted() {
	console.log(arguments);
	console.log('Reservations deleted');
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

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
