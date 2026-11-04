/**
 * The Tickets block on a recurring event: its tickets apply to every date, so the block names them so, counts their
 * sales over every date as the event's Attendees page does, and keeps its buttons in its header.
 *
 * @since TBD
 */
import React from 'react';
import { createBlock } from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@moderntribe/common/elements';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';
import { selectors } from '../../../../modules/data/blocks/ticket';
import { TICKET_LABELS } from '../../../../modules/data/blocks/ticket/constants';
import { TicketsByDateAction } from './tickets-by-date';

/**
 * Returns the sales of the event's recurring event tickets over every date, as of its last save.
 *
 * @since TBD
 *
 * @return {{tickets: Object<number, {sold: number, available: number}>, sold: number, available: number}|null} The
 *         sales, or null before the event has any.
 */
const allDatesSales = () => select( 'core/editor' ).getCurrentPost()?.tec_tickets_by_date?.sales ?? null;

/**
 * Returns a recurring event ticket's sales over every date, as of the event's last save.
 *
 * @since TBD
 *
 * @param {Object} state    The tickets store's state.
 * @param {Object} ownProps The ticket's own props.
 *
 * @return {{sold: number, available: number}|null} The sales, or null for a ticket not saved with the event yet.
 */
const ticketSales = ( state, ownProps ) =>
	allDatesSales()?.tickets?.[ selectors.getTicketId( state, ownProps ) ] ?? null;

/**
 * Adds a new ticket to the end of a Tickets block, its form open.
 *
 * @since TBD
 *
 * @param {Event}  event    The click.
 * @param {string} clientId The Tickets block's client ID.
 */
const addTicket = ( event, clientId ) => {
	// Focusing the button would select the Tickets block again and close the new ticket's form.
	event.preventDefault();

	const { getBlockCount } = select( 'core/block-editor' );

	dispatch( 'core/block-editor' ).insertBlock(
		createBlock( 'tribe/tickets-item', {} ),
		getBlockCount( clientId ),
		clientId
	);
};

/**
 * The buttons in the header of a recurring event's Tickets block.
 *
 * @since TBD
 *
 * @param {Object} props          The props.
 * @param {string} props.clientId The Tickets block's client ID.
 *
 * @return {Object} The buttons.
 */
export function RecurringTicketsActions( { clientId } ) {
	return (
		<>
			<TicketsByDateAction />
			<Button
				className="tribe-editor__action-dashboard__confirm-button tribe-editor__button--sm tribe-common-c-btn"
				onClick={ ( event ) => addTicket( event, clientId ) }
			>
				{ sprintf(
					/* Translators: %s - the singular label for a ticket. */
					__( 'Add a %s', 'event-tickets' ),
					TICKET_LABELS.ticket.singular
				) }
			</Button>
		</>
	);
}

/**
 * Titles a recurring event's Tickets block for every date, with its buttons beside the title.
 *
 * @since TBD
 *
 * @param {Object} mappedProps      The props of the block's tickets container.
 * @param {Object} context          The filter's context.
 * @param {Object} context.state    The tickets store's state.
 * @param {Object} context.ownProps The container's own props.
 *
 * @return {Object} The props.
 */
export function headRecurringTickets( mappedProps, { state, ownProps } ) {
	if ( ! hasRecurrenceRules( state ) ) {
		return mappedProps;
	}

	return {
		...mappedProps,
		header: __( 'Recurring Event Tickets', 'event-tickets' ),
		headerDescription: __( 'These tickets apply to all dates in the recurring event', 'event-tickets' ),
		headerActions: <RecurringTicketsActions clientId={ ownProps.clientId } />,
	};
}

/**
 * Shows, under a recurring event ticket's price, what it has available over every date, in parentheses when shared.
 * A ticket not saved with the event yet shows its capacity.
 *
 * @since TBD
 *
 * @param {Object} mappedProps      The props of the ticket's price.
 * @param {Object} context          The filter's context.
 * @param {Object} context.state    The tickets store's state.
 * @param {Object} context.ownProps The ticket's own props.
 *
 * @return {Object} The props.
 */
export function showRecurringTicketAvailability( mappedProps, { state, ownProps } ) {
	if ( ! hasRecurrenceRules( state ) ) {
		return mappedProps;
	}

	const sales = ticketSales( state, ownProps );
	const shown = sales
		? sales.available
		: selectors.getTicketCapacityInt( state, ownProps ) || selectors.getTicketsSharedCapacityInt( state );

	return {
		...mappedProps,
		available: selectors.isSharedTicket( state, ownProps ) ? `(${ shown })` : shown,
		amountLabel: sales ? mappedProps.amountLabel : __( 'capacity', 'event-tickets' ),
	};
}

/**
 * Counts a recurring event ticket's sold tickets, and its bar, over every date. A ticket not saved with the event yet
 * has none to count.
 *
 * @since TBD
 *
 * @param {Object} mappedProps      The props of the ticket's sold count.
 * @param {Object} context          The filter's context.
 * @param {Object} context.state    The tickets store's state.
 * @param {Object} context.ownProps The ticket's own props.
 *
 * @return {Object} The props.
 */
export function countRecurringTicketSales( mappedProps, { state, ownProps } ) {
	if ( ! hasRecurrenceRules( state ) ) {
		return mappedProps;
	}

	const sales = ticketSales( state, ownProps );

	if ( ! sales ) {
		return { ...mappedProps, isHidden: true };
	}

	const all = allDatesSales();
	const sharedCapacity = all.sold + all.available;

	return {
		...mappedProps,
		sold: sales.sold,
		capacity: mappedProps.isShared ? sharedCapacity : sales.sold + sales.available,
		sharedSold: mappedProps.isShared ? all.sold : 0,
		sharedCapacity,
	};
}

/**
 * Counts what a recurring event's tickets have available, and their capacity, over every date.
 *
 * @since TBD
 *
 * @param {Object} mappedProps   The props of the block's availability.
 * @param {Object} context       The filter's context.
 * @param {Object} context.state The tickets store's state.
 *
 * @return {Object} The props.
 */
export function countRecurringTicketsAvailability( mappedProps, { state } ) {
	const all = allDatesSales();

	if ( ! hasRecurrenceRules( state ) || ! all ) {
		return mappedProps;
	}

	return { ...mappedProps, available: all.available, total: all.sold + all.available };
}

addFilter(
	'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps',
	'tec.tickets.recurringTickets',
	headRecurringTickets,
	20
);
addFilter(
	'tec.tickets.blocks.Ticket.Price.mappedProps',
	'tec.tickets.recurringTickets',
	showRecurringTicketAvailability,
	20
);
addFilter(
	'tec.tickets.blocks.Ticket.Quantity.mappedProps',
	'tec.tickets.recurringTickets',
	countRecurringTicketSales,
	20
);
addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.recurringTickets',
	countRecurringTicketsAvailability,
	20
);
