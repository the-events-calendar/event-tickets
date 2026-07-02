/**
 * RSVP V2 Tickets Block Filters
 *
 * Registers filters to exclude RSVP V2 tickets from the Tickets block.
 * These filters ensure that tc-rsvp type tickets are handled by the RSVP block
 * and not displayed in the Tickets block.
 */

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getTicketType, isV2Enabled } from './config';

/**
 * Initialize RSVP V2 ticket block filters.
 * Only registers filters when V2 is enabled.
 */
export const initTicketsBlockFilters = () => {
	if ( ! isV2Enabled() ) {
		return;
	}

	const tcRsvpType = getTicketType();

	// Force removal of tc-rsvp tickets when fetching individual tickets so they are handled by the RSVP block.
	addFilter(
		'tec.tickets.blocks.shouldRemoveTicket',
		'tec/rsvp-v2/exclude-from-tickets-block',
		( shouldRemove, ticket ) => {
			if ( ticket?.type === tcRsvpType ) {
				return true;
			}
			return shouldRemove;
		}
	);

	// Filter out tc-rsvp tickets from the initial tickets list loaded from post meta.
	addFilter(
		'tec.tickets.blocks.initialTickets',
		'tec/rsvp-v2/exclude-from-initial-tickets',
		( tickets ) =>
			tickets.filter( ( ticket ) => ticket.type !== tcRsvpType )
	);

	// Filter out tc-rsvp tickets from tickets fetched via REST API to prevent duplicate handling.
	addFilter(
		'tec.tickets.blocks.fetchedTickets',
		'tec/rsvp-v2/exclude-from-fetched-tickets',
		( tickets ) =>
			tickets.filter( ( ticket ) => ticket.type !== tcRsvpType )
	);
};

export default initTicketsBlockFilters;
