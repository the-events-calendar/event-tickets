import './style.pcss';
import { iFrameInit } from '@tec/tickets/seating/iframe';

const { objectName } = window.tec.seating.frontend.ticketsBlock;

// {
// 	"24lk2h34kjh234": {
// 	"id": "24lk2h34kjh234",
// 		"tickets": [
// 		{
// 			"ticketId": "23",
// 			"name": "Standard Seats",
// 			"price": "$22.00",
// 			"description": "Seating for the main floor and mezzanine"
// 		},
// 		{
// 			"ticketId": "25",
// 			"name": "Children's Admission",
// 			"price": "$12.00",
// 			"description": "12 and under, standard seats only"
// 		}
// 	]
// },
// 	"kasjdfweurwur": {
// 	"id": "kasjdfweurwur",
// 		"tickets": [
// 		{
// 			"ticketId": "89",
// 			"name": "VIP Seats",
// 			"price": "$100.00",
// 			"description": "Front Row Seats"
// 		}
// 	]
// }
// }

function sendSeatTypeTickets(){
	const seatTypeMap = window.tec.seating.frontend.ticketsBlock.seatTypeMap;
}

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
