import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/iframe';
import {
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEAT_DESELECTED,
	INBOUND_SEAT_SELECTED,
	OUTBOUND_SEAT_TYPE_TICKETS,
	removeAction,
	sendPostMessage,
} from '@tec/tickets/seating/service';
import { registerAction } from '../../service/service-api';
import { TicketRow } from './ticket-row';

const { objectName, seatTypeMap, currency, labels } =
	window?.tec?.seating?.frontend?.ticketsBlock;

let totalPriceElement = null;
let totalTicketsElement = null;
const tickets = Object.values(seatTypeMap).reduce((map, seatType) => {
	seatType.tickets.map((ticket) => {
		map[ticket.ticketId] = ticket;
	});
	return map;
}, {});

function formatWithCurrency(value) {
	const [units, decimals] = value.toString().split('.');
	const formattedDecimals = decimals
		? '.' +
		  Number('.' + decimals)
				.toPrecision(currency.decimalNumbers)
				.toString()
				.slice(2)
		: '';
	const valueString =
		units
			.toString()
			// Replace the '.' with the decimal separator.
			.replace(/\./g, currency.decimalSeparator)
			// Add the thousand separator.
			.replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousandSeparator) +
		formattedDecimals;

	return currency.position === 'prefix'
		? `${currency.symbol}${valueString}`
		: `${valueString}${currency.symbol}`;
}

function formatTicketNumber(value) {
	return value === 1
		? labels.oneTicket
		: labels.multipleTickets.replace('{count}', value);
}

function updateTotals() {
	const rows = Array.from(
		document.querySelectorAll('.tec-tickets-seating__ticket-row')
	);

	totalPriceElement.innerText = formatWithCurrency(
		rows.reduce(function (acc, row) {
			return acc + Number(row.dataset.price);
		}, 0)
	);
	totalTicketsElement.innerText = formatTicketNumber(rows.length);
}

/**
 * @typedef {Object} TicketRowAddActionProps
 * @property {string} id        The seat type ID.
 * @property {string} ticketId  The ticket ID.
 * @property {string} color     The seat type color.
 * @property {string} seatLabel The seat type label.
 */

/**
 * Add a ticket to the selection.
 *
 * @since TBD
 *
 * @param {TicketRowAddActionProps} props The props for the Ticket Row component.
 *
 * @return {void} The ticket row is added to the DOM.
 */
function addTicketToSelection(props) {
	const ticketPrice = tickets?.[props.ticketId]?.price || null;
	const ticketName = tickets?.[props.ticketId].name || null;

	if (!(ticketPrice && ticketName)) {
		return;
	}

	const ticketRowProps = {
		seatTypeId: props.seatTypeId,
		ticketId: props.ticketId,
		price: ticketPrice,
		color: props.color,
		ticketName,
		seatLabel: props.seatLabel,
		formattedPrice: formatWithCurrency(ticketPrice),
	};

	document
		.querySelector('.tec-tickets-seating__ticket-rows')
		.appendChild(TicketRow(ticketRowProps));
	updateTotals();
}

/**
 * @typedef {Object} TicketRowRemoveActionProps
 * @property {string} seatTypeId The seat type ID.
 * @property {string} ticketId   The ticket ID.
 */

/**
 * Remove a ticket from the selection.
 *
 * @since TBD
 *
 * @param {string} seatTypeId The seat type ID.
 * @param {string} ticketId   The ticket ID.
 */
function removeTicketFromSelection(seatTypeId, ticketId) {
	document
		.querySelector(
			`.tec-tickets-seating__ticket-row[data-seat-type-id="${seatTypeId}"][data-ticket-id="${ticketId}"]`
		)
		?.remove();
	updateTotals();
}

function registerActions(iframe) {
	// When the service is ready for data, send the seat type map to the iframe.
	registerAction(INBOUND_APP_READY_FOR_DATA, () => {
		removeAction(INBOUND_APP_READY_FOR_DATA);
		sendPostMessage(iframe, OUTBOUND_SEAT_TYPE_TICKETS, seatTypeMap);
	});

	// When a seat is selected, add it to the selection.
	registerAction(INBOUND_SEAT_SELECTED, (seatTypeSelection) => {
		addTicketToSelection(seatTypeSelection);
	});
	// When a seat is deselected, remove it from the selection.
	registerAction(INBOUND_SEAT_DESELECTED, ({ seatTypeId, ticketId }) => {
		removeTicketFromSelection(seatTypeId, ticketId);
	});
}

async function bootstrapIframe() {
	const iframe = getIframeElement();

	if (!iframe) {
		console.error('Iframe element not found.');
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);

	await initServiceIframe(iframe);

	totalPriceElement = document.querySelector(
		'.tec-tickets-seating__total-price'
	);

	totalTicketsElement = document.querySelector(
		'.tec-tickets-seating__total-text'
	);
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

////////// TESTING //////////

waitForModalElement().then((modalElement) => {
	initModal(modalElement);
});

window.tec.seating.frontend.ticketsBlock.addStdAdultTicket = () => {
	addTicketToSelection({
		seatTypeId: 'uuid-normal',
		ticketId: 176,
		color: 'darkgreen',
		seatLabel: 'C7',
	});
};

window.tec.seating.frontend.ticketsBlock.addStdChildTicket = () => {
	addTicketToSelection({
		seatTypeId: 'uuid-normal',
		ticketId: 177,
		color: 'lightseagreen',
		seatLabel: 'G9',
	});
};

window.tec.seating.frontend.ticketsBlock.addVipTicket = () => {
	addTicketToSelection({
		seatTypeId: 'uuid-vip',
		ticketId: 178,
		color: 'blueviolet',
		seatLabel: 'A3',
	});
};

window.tec.seating.frontend.ticketsBlock.removeLastTicket = () => {
	const lastTicket = Array.from(
		document.querySelectorAll('.tec-tickets-seating__ticket-row')
	).pop();
	const seatTypeId = lastTicket.dataset?.seatTypeId;
	const ticketId = lastTicket.dataset?.ticketId;
	removeTicketFromSelection(seatTypeId, ticketId);
};
