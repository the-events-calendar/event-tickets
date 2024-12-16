import {
	setTicketHasChanges,
} from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	getTicketId,
	getTicketsProvider,
} from '@moderntribe/tickets/data/blocks/ticket/selectors';

/**
 * Dispatches an action to the common store.
 *
 * @since 5.18.0
 *
 * @param {Object} action The action to dispatch.
 */
function dispatchToCommonStore( action ) {
	window.__tribe_common_store__.dispatch( action );
}

/**
 * Selects from the common store.
 *
 * @since 5.18.0
 *
 * @param {string} selector The common store selector function to call.
 * @param {...*}   args     The arguments to call the common store selector with.
 *
 * @return {*} The result of the common store selector.
 */
function selectFromCommonStore( selector, ...args ) {
	return selector( window.__tribe_common_store__.getState(), ...args );
}

/**
 * Sets the has changes flag in the common store.
 *
 * @since 5.18.0
 *
 * @param {string} clientId The client ID of the Ticket block to update.
 */
export function setTicketHasChangesInCommonStore( clientId ) {
	dispatchToCommonStore( setTicketHasChanges( clientId, true ) );
}

/**
 * Returns the ticket post ID fetched from the common store.
 *
 * @since 5.18.0
 *
 * @param {string} clientId The client ID of the Ticket block to update.
 *
 * @return {string} The ticket ID.
 */
export function getTicketIdFromCommonStore( clientId ) {
	return selectFromCommonStore( getTicketId, { clientId } );
}

/**
 * Returns the current Ticket provider fetched from the Common store.
 *
 * @since 5.18.0
 *
 * @return {string} The current ticket Provider fetched from the Common store,
 *                  or an empty string if the Ticket block client ID is not set.
 */
export function getTicketProviderFromCommonStore() {
	try {
		return selectFromCommonStore( getTicketsProvider );
	} catch ( e ) {
		return '';
	}
}
