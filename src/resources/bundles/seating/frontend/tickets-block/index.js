import './style.pcss';
import { iFrameInit } from '@tec/tickets/seating/iframe';

const { objectName } = window.tec.seating.frontend.ticketsBlock;

function initModal(modalElement) {
	modalElement.on('show', iFrameInit);
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
