/**
 * The sales window options of the classic ticket form.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { writeRule } from './rule';

/*
 * `tickets.js` fires `pre-save-ticket.tribe` on `#tribetickets` right before it serializes the ticket form; the panels
 * are replaced after every save, so the handler is delegated from the document.
 */
jQuery( document ).on( 'pre-save-ticket.tribe', '#tribetickets', () => writeRule( document ) );
