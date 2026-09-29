/**
 * Hooks the Relative Sale Dates store into the Ticket block's fetch, save, cancel, request body and Sale Duration section.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { addAction, addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	filterSetBodyDetails,
	filterTicketDuration,
	loadTicketRule,
	resetTicketRule,
	saveTicketRule,
} from './hook-callbacks';

const namespace = 'tec.tickets.relative-sale-dates';

addFilter( 'tec.tickets.blocks.setBodyDetails', namespace, filterSetBodyDetails );
addFilter( 'tec.tickets.blocks.Ticket.Duration.renderPicker', namespace, filterTicketDuration );
addAction( 'tec.tickets.blocks.fetchTicket', namespace, loadTicketRule );
addAction( 'tec.tickets.blocks.ticketCreated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketUpdated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketCancelled', namespace, resetTicketRule );
