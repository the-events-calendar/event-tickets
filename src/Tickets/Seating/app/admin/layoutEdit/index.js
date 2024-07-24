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
import {
	ACTION_DELETE_RESERVATIONS,
	ajaxNonce,
	ajaxUrl,
} from '@tec/tickets/seating/ajax';

/**
 * Handles the deletion of reservations.
 *
 * @since TBD
 *
 * @param {string[]} ids The IDs of the reservations that were deleted.
 *
 * @return {Promise<boolean|number>} A promise that will resolve to the number of
 *                                   reservations that were deleted or `false` on failure.
 */
export async function handleReservationsDeleted(ids) {
	if (!(Array.isArray(ids) && ids.length > 0)) {
		return 0;
	}

	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('action', ACTION_DELETE_RESERVATIONS);
	const response = await fetch(url.toString(), {
		method: 'POST',
		body: JSON.stringify(ids),
	});

	if (!response.ok) {
		return false;
	}

	const json = await response.json();

	return json?.data?.numberDeleted || 0;
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

	registerAction(RESERVATIONS_DELETED, (data) =>
		handleReservationsDeleted(data.ids || [])
	);

	await initServiceIframe(getIframeElement(dom));
}

onReady(() => {
	init(document);
});
