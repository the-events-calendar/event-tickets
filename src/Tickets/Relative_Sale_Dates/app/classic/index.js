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
 * `tickets.js` fires `pre-save-ticket.tribe` on `#tribetickets` right before it serializes the ticket form. The handler
 * is bound on that element rather than delegated from the document: Event Tickets Plus stops the event from bubbling,
 * and the element itself is not replaced when the panels are.
 */
jQuery( () => {
	jQuery( '#tribetickets' ).on( 'pre-save-ticket.tribe', () => writeRule( document ) );
} );
