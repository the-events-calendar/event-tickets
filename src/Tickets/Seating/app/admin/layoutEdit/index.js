import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import { onReady } from '@tec/tickets/seating/utils';
import {
	registerAction,
	RESERVATIONS_DELETED,
	ACTION_DELETE_RESERVATIONS
} from '@tec/tickets/seating/service/api';

/**
 * Handles the deletion of reservations.
 *
 * @since TBD
 *
 * @param {string[]} ids The IDs of the reservations that were deleted.
 */
export async function handleReservationsDeleted(ids) {
	if (!(Array.isArray(ids) && ids.length > 0)) {
		return;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_DELETE_RESERVATIONS);
	url.searchParams.set('ids', ids.join(','));
	await fetch(url.toString(), {method: 'POST'});
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
