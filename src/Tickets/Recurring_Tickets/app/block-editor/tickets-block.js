/**
 * The Tickets block on a recurring event: its tickets apply to every date, so the block names them so, shows what
 * each is set up with rather than one date's sales, and keeps its buttons in its header.
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
 * Shows, under a recurring event ticket's price, its capacity, in parentheses when shared: what it sells on every
 * date. What a date has left is in Tickets by Date.
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
export function showRecurringTicketCapacity( mappedProps, { state, ownProps } ) {
	if ( ! hasRecurrenceRules( state ) ) {
		return mappedProps;
	}

	const capacity =
		selectors.getTicketCapacityInt( state, ownProps ) || selectors.getTicketsSharedCapacityInt( state );

	return {
		...mappedProps,
		available: selectors.isSharedTicket( state, ownProps ) ? `(${ capacity })` : capacity,
		amountLabel: __( 'capacity', 'event-tickets' ),
	};
}

/**
 * Leaves out a recurring event ticket's sold count and bar: its sales are a date's, in Tickets by Date.
 *
 * @since TBD
 *
 * @param {Object} mappedProps   The props of the ticket's sold count.
 * @param {Object} context       The filter's context.
 * @param {Object} context.state The tickets store's state.
 *
 * @return {Object} The props.
 */
export function hideRecurringTicketSales( mappedProps, { state } ) {
	return hasRecurrenceRules( state ) ? { ...mappedProps, isHidden: true } : mappedProps;
}

/**
 * Leaves out what a recurring event's tickets have available, a date's number, and keeps their total capacity.
 *
 * @since TBD
 *
 * @param {Object} mappedProps   The props of the block's availability.
 * @param {Object} context       The filter's context.
 * @param {Object} context.state The tickets store's state.
 *
 * @return {Object} The props.
 */
export function showRecurringTicketsCapacityOnly( mappedProps, { state } ) {
	return hasRecurrenceRules( state ) ? { ...mappedProps, available: null } : mappedProps;
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
	showRecurringTicketCapacity,
	20
);
addFilter(
	'tec.tickets.blocks.Ticket.Quantity.mappedProps',
	'tec.tickets.recurringTickets',
	hideRecurringTicketSales,
	20
);
addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.recurringTickets',
	showRecurringTicketsCapacityOnly,
	20
);
