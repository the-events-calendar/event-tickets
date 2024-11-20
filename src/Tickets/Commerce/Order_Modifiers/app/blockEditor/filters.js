/**
 * External dependencies.
 */
import { addFilter, addAction } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	filterSetBodyDetails,
	filterTicketContainerItems,
	setFeesForTicket,
	updateFeesForTicket,
} from './hook-callbacks';

// The namespace for the filters.
const namespace = 'tec.tickets.order-modifiers.fees';

// Add the fees to the body details of the ticket.
addFilter(
	'tec.tickets.blocks.setBodyDetails',
	namespace,
	filterSetBodyDetails
);

// Add the Fees component to the ticket container.
addFilter(
	'tec.ticket.container.items',
	namespace,
	filterTicketContainerItems
);

// Set the fees for the ticket.
addAction(
	'tec.tickets.blocks.fetchTicket',
	namespace,
	setFeesForTicket
);

addAction(
	'tec.tickets.blocks.ticketUpdated',
	namespace,
	updateFeesForTicket
);

addAction(
	'tec.tickets.blocks.ticketCreated',
	namespace,
	updateFeesForTicket
);
