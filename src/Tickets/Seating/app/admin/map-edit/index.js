import './style.pcss';
import { onReady } from '@tec/tickets/seating/utils';
import {
	initServiceIframe,
	getIframeElement,
} from '@tec/tickets/seating/service/iframe';
import {
	registerAction,
} from '@tec/tickets/seating/service/api';

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
	await initServiceIframe(getIframeElement(dom));
}
onReady(() => {
	init(document);
});

