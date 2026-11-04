/**
 * Recurring event tickets in the block editor's Tickets block.
 *
 * On a recurring event the Tickets block shows its dashboard and "Add a Ticket": the server stores a ticket saved
 * on a recurring event as a recurring event ticket. The RSVP block is not filtered.
 *
 * @since TBD
 */
import { addFilter } from '@wordpress/hooks';
import { constants, selectors } from '../../../../modules/data/blocks/ticket';

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
 * Gives the Tickets block's dashboard on a recurring event what it has on a single event: "Add a Ticket", the
 * settings, and the attendees and orders links once the event has tickets.
 *
 * Flexible Tickets takes them away on a recurring event, written for when it could only sell Series Passes: it hides
 * the settings, and links the attendees and orders only to Series Passes.
 *
 * @since TBD
 *
 * @param {Object}  mappedProps         The dashboard's props.
 * @param {Object}  context             The filter's context.
 * @param {Object}  context.state       The tickets store's state.
 * @param {boolean} context.isRecurring Whether the event has recurrence rules.
 *
 * @return {Object} The dashboard's props.
 */
export function offerTicketsOnRecurringEvents( mappedProps, { state, isRecurring } ) {
	if ( ! isRecurring ) {
		return mappedProps;
	}

	const provider = selectors.getTicketsProvider( state );

	return {
		...mappedProps,
		showConfirm: true,
		showNotSupportedMessage: false,
		showWarning: false,
		disableSettings: false,
		// Flexible Tickets leaves Series Passes here: the event's own tickets count too.
		hasCreatedTickets: selectors.hasCreatedTickets( state ) || Boolean( mappedProps.hasCreatedTickets ),
		hasOrdersPage: Boolean( constants.TICKET_ORDERS_PAGE_SLUG[ provider ] ),
	};
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
