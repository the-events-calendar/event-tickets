/**
 * Hooks the Relative Sale Dates store into the Ticket block's fetch, save, cancel, request body, Sale Duration section,
 * sale price dates, header sale window, Create or Update button and the sync of its sale end with the event start.
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
	filterSalePricePickers,
	filterSaleWindowDates,
	filterSetBodyDetails,
	filterSyncSaleEndWithEventStart,
	filterTicketDuration,
	loadTicketRule,
	resetTicketRule,
	saveTicketRule,
} from './hook-callbacks';

const namespace = 'tec.tickets.relative-sale-dates';

addFilter( 'tec.tickets.blocks.setBodyDetails', namespace, filterSetBodyDetails );
addFilter( 'tec.tickets.blocks.Ticket.Duration.renderPicker', namespace, filterTicketDuration );
addFilter( 'tec.tickets.blocks.Ticket.SalePrice.renderPickers', namespace, filterSalePricePickers );
addFilter( 'tec.tickets.blocks.Ticket.SaleWindow.dates', namespace, filterSaleWindowDates );
addFilter( 'tec.tickets.blocks.confirmButton.isDisabled', namespace, filterConfirmDisabled );
addFilter( 'tec.tickets.blocks.syncSaleEndWithEventStart', namespace, filterSyncSaleEndWithEventStart );
addAction( 'tec.tickets.blocks.fetchTicket', namespace, loadTicketRule );
addAction( 'tec.tickets.blocks.ticketCreated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketUpdated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketCancelled', namespace, resetTicketRule );
