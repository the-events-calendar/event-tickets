import { createHtmlComponentFromTemplateString } from '@tec/tickets/seating/utils';

/**
 * @typedef {Object} TicketRowProps
 * @property {string} seatTypeId     The seat type ID.
 * @property {string} ticketId       The ticket ID.
 * @property {number} price          The price of the ticket in the provider currency.
 * @property {string} color          The color of the seat type, valid CSS color, e.g. "#d02697".
 * @property {string} ticketName     The name of the ticket, e.g. "VIP".
 * @property {string} seatLabel      The label of the seat, e.g. "C7".
 * @property {string} formattedPrice The formatted price of the ticket, e.g. "$40.00".
 */

/**
 * Create a Ticket Row component for the Seat Selection modal ticket block.
 *
 * @since 5.16.0
 *
 * @param {TicketRowProps} props The props for the Ticket Row component.
 *
 * @return {HTMLElement} The HTML element for the Ticket Row component.
 */
export function TicketRow(props) {
	return createHtmlComponentFromTemplateString(
		`<div class="tec-tickets-seating__ticket-row"
			data-seat-type-id="{seatTypeId}"
			data-ticket-id="{ticketId}"
			data-price="{price}"
			data-seat-label="{seatLabel}"
			>
			<div class="tec-tickets-seating__seat-color" style="background: {color}"></div>

			<div class="tec-tickets-seating__label">
				<div class="tec-tickets-seating__ticket-name">{ticketName}</div>
				<div class="tec-tickets-seating__seat-label">{seatLabel}</div>
			</div>

			<div class="tec-tickets-seating__ticket-price">{formattedPrice}</div>
		</div>`,
		props
	);
}
