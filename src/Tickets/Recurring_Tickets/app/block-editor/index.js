/**
 * Recurring event tickets in the block editor's Tickets block.
 *
 * On a recurring event the Tickets block shows its dashboard and "Add a Ticket": the server stores a ticket saved
 * on a recurring event as a recurring event ticket. The RSVP block is not filtered.
 *
 * @since TBD
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Lets the Tickets block render its dashboard on a recurring event.
 *
 * @since TBD
 *
 * @param {Object} mappedProps The props of the Tickets block.
 *
 * @return {Object} The props.
 */
export function allowTicketsOnRecurringEvents( mappedProps ) {
	return { ...mappedProps, noTicketsOnRecurring: false };
}

/**
 * Offers "Add a Ticket" on a recurring event, without the message that tickets are not supported there.
 *
 * Runs after Flexible Tickets, which hides the button on a recurring event.
 *
 * @since TBD
 *
 * @param {Object}  mappedProps         The props of the dashboard's actions.
 * @param {Object}  context             The context of the filter.
 * @param {boolean} context.isRecurring Whether the event recurs.
 *
 * @return {Object} The props.
 */
export function offerTicketsOnRecurringEvents( mappedProps, { isRecurring } ) {
	if ( ! isRecurring ) {
		return mappedProps;
	}

	return { ...mappedProps, showConfirm: true, showNotSupportedMessage: false, showWarning: false };
}

addFilter(
	'tec.tickets.blocks.Tickets.mappedProps',
	'tec.tickets.recurringTickets',
	allowTicketsOnRecurringEvents,
	20
);
addFilter(
	'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps',
	'tec.tickets.recurringTickets',
	offerTicketsOnRecurringEvents,
	20
);
