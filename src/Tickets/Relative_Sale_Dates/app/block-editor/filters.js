/**
 * Hooks the Relative Sale Dates store into the Ticket block's fetch, save, cancel, request body, Sale Duration section,
 * sale price dates, header sale window, header on sale state, Create or Update button, the sync of its sale end with
 * the event start and its update with the post save.
 *
 * @since 5.31.0
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
	filterConfirmHasDurationError,
	filterSalePricePickers,
	filterSaleWindowDates,
	filterSetBodyDetails,
	filterSyncSaleEndWithEventStart,
	filterTicketDuration,
	filterTicketOnSale,
	filterUpdateTicketWithPostSave,
	loadTicketRule,
	resetTicketRule,
	saveTicketRule,
} from './hook-callbacks';

const namespace = 'tec.tickets.relative-sale-dates';

addFilter( 'tec.tickets.blocks.setBodyDetails', namespace, filterSetBodyDetails );
addFilter( 'tec.tickets.blocks.Ticket.Duration.renderPicker', namespace, filterTicketDuration );
addFilter( 'tec.tickets.blocks.Ticket.SalePrice.renderPickers', namespace, filterSalePricePickers );
addFilter( 'tec.tickets.blocks.Ticket.SaleWindow.dates', namespace, filterSaleWindowDates );
addFilter( 'tec.tickets.blocks.Ticket.onSale', namespace, filterTicketOnSale );
addFilter( 'tec.tickets.blocks.confirmButton.hasDurationError', namespace, filterConfirmHasDurationError );
addFilter( 'tec.tickets.blocks.confirmButton.isDisabled', namespace, filterConfirmDisabled );
addFilter( 'tec.tickets.blocks.syncSaleEndWithEventStart', namespace, filterSyncSaleEndWithEventStart );
addFilter( 'tec.tickets.blocks.updateTicketWithPostSave', namespace, filterUpdateTicketWithPostSave );
addAction( 'tec.tickets.blocks.fetchTicket', namespace, loadTicketRule );
addAction( 'tec.tickets.blocks.ticketCreated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketUpdated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketCancelled', namespace, resetTicketRule );
