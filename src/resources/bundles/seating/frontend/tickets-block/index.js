import './style.pcss';
import { iFrameInit } from '@tec/tickets/seating/iframe';

const { objectName } = window.tec.seating.frontend.ticketsBlock;

async function bootstrapIframe() {
	const initialized = await iFrameInit();
	const iframe = initialized?.[0] || null;

	if (!iframe) {
		console.error('Iframe initialization failed.');
		return false;
	}

	sendSeatTypeTickets();
}

function initModal(modalElement) {
	modalElement.on('show', bootstrapIframe);
}

/**
 * Waits for the modal element to be present in the DOM.
 *
 * @return {Promise<Element>} A promise that resolves to the modal element.
 */
async function waitForModalElement() {
	return new Promise((resolve) => {
		const check = () => {
			if (window[objectName]) {
				resolve(window[objectName]);
			}
			setTimeout(check, 50);
		};

		check();
	});
}

waitForModalElement().then((modalElement) => {
	initModal(modalElement);
});
