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
	getToken,
} from '@tec/tickets/seating/service/api';
import { TicketRow } from './ticket-row';
import { localizedData } from './localized-data';
import { formatWithCurrency } from '@tec/tickets/seating/currency';
import { getCheckoutHandlerForProvider } from './checkout-handlers';
import {
	start as startTimer,
	reset as resetTimer,
} from '@tec/tickets/seating/frontend/session';
import './filters';

const {
	objectName,
	seatTypeMap,
	labels,
	providerClass,
	postId,
	ajaxUrl,
	ajaxNonce,
	ACTION_POST_RESERVATIONS,
	ACTION_CLEAR_RESERVATIONS,
} = localizedData;

/**
 * The total price element.
 *
 * @since TBD
 *
 * @type {HTMLElement|null}
 */
let totalPriceElement = null;

/**
 * The total tickets element.
 *
 * @since TBD
 *
 * @type {HTMLElement|null}
 */
let totalTicketsElement = null;

/**
 * The Confirm button selector.
 *
 * @since TBD
 *
 * @type {string}
 */
const confirmSelector =
	'.tec-tickets-seating__modal .tec-tickets-seating__sidebar-control--confirm';

/**
 * @typedef {Object} SeatMapTicketEntry
 * @property {string} ticketId    The ticket ID.
 * @property {string} name        The ticket name.
 * @property {number} price       The ticket price.
 * @property {string} description The ticket description.
 */

/**
 * The tickets map.
 *
 * @since TBD
 *
 * @type {Object<string, SeatMapTicketEntry>}
 */
const tickets = Object.values(seatTypeMap).reduce((map, seatType) => {
	seatType.tickets.forEach((ticket) => {
		map[ticket.ticketId] = ticket;
	});
	return map;
}, {});

/**
 * The current fetch signal handler.
 *
 * @since TBD
 *
 * @type {AbortController}
 */
let currentController = new AbortController();

/**
 * Whether the reservations should be cancelled on hide or destroy of the seat selection modal or not.
 * By default, the reservations will be cancelled, but this flag will be set to `false` during checkout.
 *
 * @since TBD
 *
 * @type {boolean}
 */
let shouldCancelReservations = true;

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
 * @param {HTMLElement|null} parentElement The parent element to disable the checkout button for.
 *
 * @return {void}
 */
function enableCheckout(parentElement) {
	parentElement = parentElement || document;
	Array.from(parentElement.querySelectorAll(confirmSelector)).forEach(
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
 * @param {HTMLElement|null} parentElement The parent element to enable the checkout button for.
 *
 * @return {void}
 */
function disableCheckout(parentElement) {
	parentElement = parentElement || document;
	Array.from(parentElement.querySelectorAll(confirmSelector)).forEach(
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
 * @param {HTMLElement|null} parentElement The parent element to update the totals for.
 *
 * @return {void} The total prices and number of tickets are updated.
 */
function updateTotals(parentElement) {
	parentElement = parentElement || document;
	const rows = Array.from(
		parentElement.querySelectorAll('.tec-tickets-seating__ticket-row')
	);

	if (rows.length) {
		enableCheckout(parentElement);
	} else {
		disableCheckout(parentElement);
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
 * @property {string} reservationId The reservation UUID.
 * @property {string} seatColor     The seat type color.
 * @property {string} seatLabel     The seat type label.
 * @property {string} seatTypeId    The seat type ID.
 * @property {string} ticketId      The ticket ID.
 */

/**
 * Add a ticket to the selection.
 *
 * @since TBD
 *
 * @param {HTMLElement|null}     parentElement The parent element to add the ticket to.
 * @param {TicketSelectionProps} props         The props for the Ticket Row component.
 *
 * @return {void} The ticket row is added to the DOM.
 */
function addTicketToSelection(parentElement, props) {
	parentElement = parentElement || document;
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

	parentElement
		.querySelector('.tec-tickets-seating__ticket-rows')
		.appendChild(TicketRow(ticketRowProps));
}

/**
 * Posts the reservations to the backend replacing the existing ones.
 *
 * @since TBD
 *
 * @param {Object} reservations The reservation IDs to post to the backend.
 */
async function postReservationsToBackend(reservations) {
	// First of all, cancel any similar requests that might be in progress.
	await currentController.abort('New reservations data');
	const newController = new AbortController();

	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajax_nonce', ajaxNonce);
	requestUrl.searchParams.set('action', ACTION_POST_RESERVATIONS);
	requestUrl.searchParams.set('postId', postId);
	let response = null;

	response = await fetch(requestUrl.toString(), {
		method: 'POST',
		signal: newController.signal,
		body: JSON.stringify({
			token: getToken(),
			reservations,
		}),
	});

	currentController = newController;

	if (!response.ok) {
		console.error('Failed to post reservations to backend');
		return false;
	}

	return true;
}

/**
 * Updates the tickets selection.
 *
 * @since TBd
 *
 * @param {HTMLElement|null}       parentElement The parent element to add the tickets to.
 * @param {TicketSelectionProps[]} items         The items to add to the selection.
 */
function updateTicketsSelection(parentElement, items) {
	parentElement = parentElement || document;
	parentElement.querySelector('.tec-tickets-seating__ticket-rows').innerHTML =
		'';

	items.forEach((item) => {
		addTicketToSelection(parentElement, item);
	});

	const reservations = items.reduce((acc, item) => {
		acc[item.ticketId] = acc[item.ticketId] || [];
		acc[item.ticketId].push({
			reservationId: item.reservationId,
			seatTypeId: item.seatTypeId,
			seatLabel: item.seatLabel,
		});
		return acc;
	}, {});

	postReservationsToBackend(reservations);

	updateTotals(parentElement);
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
	return (
		item.seatTypeId &&
		item.ticketId &&
		item.seatColor &&
		item.seatLabel &&
		item.reservationId
	);
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
			iframe.closest('.event-tickets'),
			items.filter((item) => validateSelectionItemFromService(item))
		);
	});
}

/**
 * Bootstraps the service iframe starting the communication with the service.
 *
 * @since TBd
 *
 * @param {HTMLDocument|null} dom The document to use to bootstrap the iframe.
 *
 * @return {Promise<boolean>} A promise that resolves to true if the iframe is ready to communicate with the service.
 */
export async function bootstrapIframe(dom) {
	dom = dom || document;
	const iframe = getIframeElement(dom);

	if (!iframe) {
		console.error('Iframe element not found.');
		return false;
	}

	// Register the actions before initializing the iframe to avoid race conditions.
	registerActions(iframe);

	await initServiceIframe(iframe);

	totalPriceElement = dom.querySelector('.tec-tickets-seating__total-price');

	totalTicketsElement = dom.querySelector('.tec-tickets-seating__total-text');
}

/**
 * Prompts the backend to cancel the reservations.
 *
 * @since TBD
 *
 * @return {Promise<boolean>} A promise that resolves to `true` if the reservations were removed successfully,
 *                            `false` otherwise.
 */
async function cancelReservationsOnBackend() {
	// First of all, cancel any similar requests that might be in progress.
	await currentController.abort('New reservations data');
	const newController = new AbortController();

	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajax_nonce', ajaxNonce);
	requestUrl.searchParams.set('action', ACTION_CLEAR_RESERVATIONS);
	requestUrl.searchParams.set('token', getToken());
	requestUrl.searchParams.set('postId', postId);

	const response = await fetch(requestUrl.toString(), {
		signal: newController.signal,
		method: 'POST',
	});

	currentController = newController;

	if (!response.ok) {
		console.error('Failed to remove reservations from backend');
		return false;
	}

	return true;
}

/**
 * Clears the ticket selection from the DOM.
 *
 * @since TBD
 *
 * @return {void} The ticket selection is cleared.
 */
function clearTicketSelection() {
	Array.from(
		document.querySelectorAll(
			'.tec-tickets-seating__ticket-rows .tec-tickets-seating__ticket-row'
		)
	).forEach((row) => {
		row.remove();
	});
}

/**
 * Dispatches a clear reservations message to the service through the iframe.
 *
 * @since TBD
 *
 * @param {HTMLElement} dialogElement the iframe element that should be used to communicate with the service.
 */
export async function cancelReservations(dialogElement) {
	if (!shouldCancelReservations) {
		return;
	}

	const iframe = dialogElement.querySelector(
		'.tec-tickets-seating__iframe-container iframe.tec-tickets-seating__iframe'
	);

	if (iframe) {
		sendPostMessage(iframe, OUTBOUND_REMOVE_RESERVATIONS);
	}

	await cancelReservationsOnBackend();
	resetTimer();
	clearTicketSelection();
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
	// The seat selection modal will be hidden or destroyed, so we should not cancel the reservations.
	shouldCancelReservations = false;
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

	shouldCancelReservations = true;
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

	modal.on('hide', cancelReservations);
	modal.on('destroy', cancelReservations);
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
		bootstrapIframe(document);
		addModalEventListeners();
	});
});

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.frontend = window.tec.tickets.seating.frontend || {};
window.tec.tickets.seating.frontend.ticketsBlock = {
	...(window.tec.tickets.seating.frontend.ticketsBlock || {}),
	cancelReservations,
};
