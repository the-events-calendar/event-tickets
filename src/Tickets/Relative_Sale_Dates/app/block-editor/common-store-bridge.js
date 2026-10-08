/**
 * Reads and writes the legacy ticket store the Ticket block runs on.
 *
 * The actions and selectors come from the Ticket block's own script at runtime, so this bundle does not carry a second
 * copy of the legacy ticket code. The store is common's shared one.
 *
 * @since TBD
 */

/**
 * The provider class the tickets block holds when it sells its tickets through Tickets Commerce.
 *
 * @since TBD
 *
 * @type {string}
 */
const TICKETS_COMMERCE = 'TEC\\Tickets\\Commerce\\Module';

/**
 * The provider slug the block editor tickets REST API gives a Tickets Commerce ticket, which a fetched ticket keeps.
 *
 * @since TBD
 *
 * @type {string}
 */
export const TICKETS_COMMERCE_PROVIDER = 'tc';

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
 * Returns whether a ticket is sold through Tickets Commerce, the only provider the rule applies to.
 *
 * A ticket fetched from the server keeps its own provider, which an event's older tickets may not share with the tickets
 * block; the server judges the ticket by it. A new ticket has none yet and is created with the block's, which the admin
 * can change while editing, so both are read from the store rather than from localized data.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {boolean} Whether the ticket is sold through Tickets Commerce.
 */
export function isTicketsCommerce( clientId ) {
	const { selectors } = getTicketData();
	const state = window.__tribe_common_store__.getState();
	const ticketProvider = selectors.getTicketProvider( state, { clientId } );

	if ( ticketProvider ) {
		return TICKETS_COMMERCE_PROVIDER === ticketProvider;
	}

	return TICKETS_COMMERCE === selectors.getTicketsProvider( state );
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
