import './style.pcss';
import {
	getIframeElement,
	initServiceIframe,
} from '@tec/tickets/seating/service/iframe';
import {
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEATS_SELECTED,
	OUTBOUND_SEAT_TYPE_TICKETS,
	OUTBOUND_REMOVE_RESERVATIONS,
	removeAction,
	registerAction,
	sendPostMessage,
} from '@tec/tickets/seating/service';
import { TicketRow } from './ticket-row';
import { externals } from './externals';
import { formatWithCurrency } from '@tec/tickets/seating/currency';
import { getCheckoutHandlerForProvider } from './checkout-handlers';
import { start as startTimer } from './timer';

const { objectName, seatTypeMap, labels, providerClass, postId } = externals;

let totalPriceElement = null;
let totalTicketsElement = null;

const confirmSelector =
	'.tec-tickets-seating__modal .tec-tickets-seating__sidebar-control--confirm';

const tickets = Object.values(seatTypeMap).reduce((map, seatType) => {
	seatType.tickets.forEach((ticket) => {
		map[ticket.ticketId] = ticket;
	});
	return map;
}, {});

/**
 * Formats the text representing the total number of tickets selected.
 *
 * @since TBD
 *
 * @param {number} value The value to format.
 *
 * @return {string} The formatted value.
 */
function formatTicketNumber(value) {
	return value === 1
		? labels.oneTicket
		: labels.multipleTickets.replace('{count}', value);
}

/**
 * Disable the Checkout confirmation button(s).
 *
 * @since TBD
 *
 * @return {void}
 */
function enableCheckout() {
	Array.from(document.querySelectorAll(confirmSelector)).forEach(
		(confirm) => {
			confirm.disabled = false;
		}
	);
}

/**
 * Enables the Checkout confirmation button(s).
 *
 * @since TBD
 *
 * @return {void}
 */
function disableCheckout() {
	Array.from(document.querySelectorAll(confirmSelector)).forEach(
		(confirm) => {
			confirm.disabled = true;
		}
	);
}

/**
 * Updates the total prices and number of tickets in the block.
 *
 * @since TBD
 *
 * @return {void} The total prices and number of tickets are updated.
 */
function updateTotals() {
	const rows = Array.from(
		document.querySelectorAll('.tec-tickets-seating__ticket-row')
	);

	if (rows.length) {
		enableCheckout();
	} else {
		disableCheckout();
	}

	totalPriceElement.innerText = formatWithCurrency(
		rows.reduce(function (acc, row) {
			return acc + Number(row.dataset.price);
		}, 0)
	);
	totalTicketsElement.innerText = formatTicketNumber(rows.length);
}

/**
 * @typedef {Object} TicketSelectionProps
 * @property {string} seatTypeId The seat type ID.
 * @property {string} ticketId   The ticket ID.
 * @property {string} seatColor  The seat type color.
 * @property {string} seatLabel  The seat type label.
 */

/**
 * Add a ticket to the selection.
 *
 * @since TBD
 *
 * @param {TicketSelectionProps} props The props for the Ticket Row component.
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
		color: props.seatColor,
		ticketName,
		seatLabel: props.seatLabel,
		formattedPrice: formatWithCurrency(ticketPrice),
	};

	document
		.querySelector('.tec-tickets-seating__ticket-rows')
		.appendChild(TicketRow(ticketRowProps));
}

/**
 * Updates the tickets selection.
 *
 * @since TBd
 *
 * @param {TicketSelectionProps[]} items
 */
function updateTicketsSelection(items) {
	document.querySelector('.tec-tickets-seating__ticket-rows').innerHTML = '';

	items.forEach((item) => {
		addTicketToSelection(item);
	});

	updateTotals();
}

/**
 * Validates a selection item received from the service is valid.
 *
 * @since TBD
 *
 * @param {Object} item The item to validate.
 *
 * @return {boolean} True if the item is valid, false otherwise.
 */
function validateSelectionItemFromService(item) {
	return item.seatTypeId && item.ticketId && item.seatColor && item.seatLabel;
}

/**
 * Registers the handlers for the msssages received from the service.
 *
 * @since TBD
 *
 *
 * @param {HTMLElement} iframe The service iframe element to listen to.
 */
function registerActions(iframe) {
	// When the service is ready for data, send the seat type map to the iframe.
	registerAction(INBOUND_APP_READY_FOR_DATA, () => {
		removeAction(INBOUND_APP_READY_FOR_DATA);
		sendPostMessage(iframe, OUTBOUND_SEAT_TYPE_TICKETS, seatTypeMap);
	});

	// When a seat is selected, add it to the selection.
	registerAction(INBOUND_SEATS_SELECTED, (items) => {
		updateTicketsSelection(
			items.filter((item) => validateSelectionItemFromService(item))
		);
	});
}

/**
 * Bootstraps the service iframe starting the communication with the service.
 *
 * @since TBd
 *
 * @return {Promise<boolean>} A promise that resolves to true if the iframe is ready to communicate with the service.
 */
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

/**
 * Dispatches a clear reservations message to the service through the iframe.
 *
 * @since TBD
 *
 * @param {HTMLElement} dialogElement the iframe element that should be used to communicate with the service.
 */
export function removeReservationsThroughIframe(dialogElement) {
	const iframe = dialogElement.querySelector(
		'.tec-tickets-seating__iframe-container iframe.tec-tickets-seating__iframe'
	);

	if (!iframe) {
		return;
	}

	sendPostMessage(iframe, OUTBOUND_REMOVE_RESERVATIONS);
}

/**
 * Closes the modal element using its reference on the window object.
 *
 * @since TBD
 *
 * @return {void} The modal is closed.
 */
export function closeModal() {
	const modal = window?.[objectName];

	if (!modal) {
		return;
	}

	modal._hide();
}

/**
 * @typedef {Object} SelectedTicket
 * @property {string} ticket_id The ticket ID.
 * @property {number} quantity  The quantity of the ticket.
 * @property {string} optout    Whether the ticket is opted out or not.
 */

/**
 * Reads and compiles a list of the selected tickets from the DOM
 *
 * @since TBD
 *
 * @return {SelectedTicket[]} A list of the selected tickets.
 */
function readTicketsFromSelection() {
	const ticketsFromSelection = Array.from(
		document.querySelectorAll(
			'.tec-tickets-seating__ticket-rows .tec-tickets-seating__ticket-row'
		)
	).reduce((acc, row) => {
		const ticketId = row.dataset.ticketId;

		if (!acc?.[ticketId]) {
			acc[ticketId] = {
				ticket_id: ticketId,
				quantity: 1,
				optout: '1', // @todo: actually pull this from the Attendee data collection.
				seat_labels: [row.dataset.seatLabel],
			};
		} else {
			acc[ticketId].quantity++;
			acc[ticketId].seat_labels = [
				...acc[ticketId].seat_labels,
				row.dataset.seatLabel,
			];
		}

		return acc;
	}, {});

	return Object.values(ticketsFromSelection);
}

/**
 * Proceeds to the checkout phase according to the provider.
 *
 * @since TBD
 *
 * @return {Promise<void>} A promise that resolves to void. Note that, most likely, the checkout will redirect to the
 *                          provider's checkout page.
 */
async function proceedToCheckout() {
	const checkoutHandler = getCheckoutHandlerForProvider(providerClass);

	if (!checkoutHandler) {
		console.error(
			`No checkout handler found for provider ${providerClass}`
		);
		return;
	}

	const data = new FormData();
	data.append('provider', providerClass);

	data.append('attendee[optout]', '1');
	data.append('tickets_tickets_ar', '1');

	const selectedTickets = readTicketsFromSelection();

	data.append('tribe_tickets_saving_attendees', '1');
	data.append(
		'tribe_tickets_ar_data',
		JSON.stringify({
			tribe_tickets_tickets: selectedTickets,
			tribe_tickets_meta: [],
			tribe_tickets_post_id: postId,
		})
	);

	const ok = await checkoutHandler(data);

	if (!ok) {
		console.error('Failed to proceed to checkout.');
	}
}

/**
 * Adds event listeners to the modal element once it's loaded.
 *
 * @since TBD
 *
 * @return {void} Adds event listeners to the modal element once it's loaded.
 */
export function addModalEventListeners() {
	document
		.querySelector(
			'.tec-tickets-seating__modal .tec-tickets-seating__sidebar-control--cancel'
		)
		?.addEventListener('click', closeModal);
	document
		.querySelector(confirmSelector)
		?.addEventListener('click', proceedToCheckout);

	startTimer();

	const modal = window[objectName];

	if (!modal) {
		return;
	}

	modal.on('hide', removeReservationsThroughIframe);
	modal.on('destroy', removeReservationsThroughIframe);
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
	modalElement.on('show', () => {
		disableCheckout();
		bootstrapIframe();
		addModalEventListeners();
	});
});
