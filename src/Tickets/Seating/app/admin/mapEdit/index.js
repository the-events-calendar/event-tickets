import './style.pcss';
import { onReady } from '@tec/tickets/seating/utils';
import {
	initServiceIframe,
	getIframeElement,
	handleResize,
} from '@tec/tickets/seating/service/iframe';
import {
	registerAction,
	INBOUND_SET_ELEMENT_HEIGHT,
} from '@tec/tickets/seating/service/api';

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

	registerAction(INBOUND_SET_ELEMENT_HEIGHT, (data) => handleResize( data, dom ) );

	await initServiceIframe(getIframeElement(dom));
}
onReady(() => {
	init(document);
});

