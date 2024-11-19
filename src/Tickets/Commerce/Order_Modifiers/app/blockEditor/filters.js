/**
 * External dependencies.
 */
import { addFilter, addAction } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	filterSetBodyDetails,
	filterTicketContainerItems
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
