/**
 * Reads and writes the legacy ticket store the Ticket block runs on.
 *
 * The actions and selectors come from the Ticket block's own script at runtime, so this bundle does not carry a second
 * copy of the legacy ticket code. The store is common's shared one.
 *
 * @since TBD
 */

const TICKETS_COMMERCE = 'TEC\\Tickets\\Commerce\\Module';

/**
 * Returns the legacy ticket actions and selectors.
 *
 * @since TBD
 *
 * @return {{actions: Object, selectors: Object}} The actions and selectors.
 */
function getTicketData() {
	return window.tribe.tickets.data.blocks;
}

/**
 * Returns whether the tickets block sells its tickets through Tickets Commerce, the only provider the rule applies to.
 *
 * The admin can change the provider while editing, so it is read from the store rather than from localized data.
 *
 * @since TBD
 *
 * @return {boolean} Whether the provider is Tickets Commerce.
 */
export function isTicketsCommerce() {
	return (
		TICKETS_COMMERCE === getTicketData().selectors.getTicketsProvider( window.__tribe_common_store__.getState() )
	);
}

/**
 * Marks a ticket as changed, which enables its Create or Update button.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function markTicketChanged( clientId ) {
	window.__tribe_common_store__.dispatch( getTicketData().actions.setTicketHasChanges( clientId, true ) );
}
