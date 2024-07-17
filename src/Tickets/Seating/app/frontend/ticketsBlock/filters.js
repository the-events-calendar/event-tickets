import { addFilter } from '@wordpress/hooks';
import { localizedData } from './localized-data';

/**
 * The list of ticket IDs that is checked for availability in the Tickets Block.
 *
 * @since TBD
 *
 * @type {number[]}
 */
const ticketIds = Object.values(localizedData.seatTypeMap).reduce(
	(acc, seatType) => {
		acc.push(...seatType.tickets.map((ticket) => ticket.ticketId));
		return acc;
	},
	[]
);

/**
 * Filters the list of Ticket IDS that is checked for availability in the Tickets Block.
 *
 * @since TBD
 *
 * @return {number[]} The filtered list of Ticket IDS that is checked for availability in the Tickets Block.
 */
export function filterGeTickets() {
	return ticketIds;
}

// The default logic will not find any ticket to check for availability, so we need to filter it.
addFilter(
	'tec.tickets.tickets-block.getTickets',
	'tec.tickets.seating',
	filterGeTickets
);
