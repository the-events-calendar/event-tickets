/**
 * Hooks the Relative Sale Dates store into the Ticket block's fetch, save, cancel, request body, Sale Duration section,
 * header sale window and Create or Update button.
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
	filterConfirmDisabled,
	filterSaleWindowDates,
	filterSetBodyDetails,
	filterTicketDuration,
	loadTicketRule,
	resetTicketRule,
	saveTicketRule,
} from './hook-callbacks';

const namespace = 'tec.tickets.relative-sale-dates';

addFilter( 'tec.tickets.blocks.setBodyDetails', namespace, filterSetBodyDetails );
addFilter( 'tec.tickets.blocks.Ticket.Duration.renderPicker', namespace, filterTicketDuration );
addFilter( 'tec.tickets.blocks.Ticket.SaleWindow.dates', namespace, filterSaleWindowDates );
// Ahead of other extensions, so it only overrides the core value and never re-enables a button one of them disabled.
addFilter( 'tec.tickets.blocks.confirmButton.isDisabled', namespace, filterConfirmDisabled, 5 );
addAction( 'tec.tickets.blocks.fetchTicket', namespace, loadTicketRule );
addAction( 'tec.tickets.blocks.ticketCreated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketUpdated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketCancelled', namespace, resetTicketRule );
