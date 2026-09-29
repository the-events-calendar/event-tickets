/**
 * Hooks the Relative Sale Dates store into the Ticket block's fetch, save, cancel and request body.
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
import { filterSetBodyDetails, loadTicketRule, resetTicketRule, saveTicketRule } from './hook-callbacks';

const namespace = 'tec.tickets.relative-sale-dates';

addFilter( 'tec.tickets.blocks.setBodyDetails', namespace, filterSetBodyDetails );
addAction( 'tec.tickets.blocks.fetchTicket', namespace, loadTicketRule );
addAction( 'tec.tickets.blocks.ticketCreated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketUpdated', namespace, saveTicketRule );
addAction( 'tec.tickets.blocks.ticketCancelled', namespace, resetTicketRule );
