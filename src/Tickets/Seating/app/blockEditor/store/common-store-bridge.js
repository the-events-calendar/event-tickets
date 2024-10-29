import {
	setTicketCapacity,
	setTicketCapacityType,
	setTicketHasChanges,
	setTicketsSharedCapacity,
	setTicketsTempSharedCapacity,
	setTicketTempCapacity,
	setTicketTempCapacityType,
} from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	getTicketId,
	getTicketsSharedCapacityInt,
	getTicketsProvider,
} from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { CAPPED } from '@moderntribe/tickets/data/blocks/ticket/constants';

/**
 * Dispatches an action to the common store.
 *
 * @since 5.16.0
 *
 * @param {Object} action The action to dispatch.
 */
function dispatchToCommonStore(action) {
	window.__tribe_common_store__.dispatch(action);
}

/**
 * Selects from the common store.
 *
 * @since 5.16.0
 *
 * @param {string} selector The common store selector function to call.
 * @param {...*}   args     The arguments to call the common store selector with.
 *
 * @return {*} The result of the common store selector.
 */
function selectFromCommonStore(selector, ...args) {
	return selector(window.__tribe_common_store__.getState(), ...args);
}

/**
 * Sets the shared capacity in the common store.
 *
 * @since 5.16.0
 *
 * @param {string} clientId The client ID of Ticket block to set the capacity for.
 * @param {number} capacity The capacity to set.
 */
export function setTicketsSharedCapacityInCommonStore(clientId, capacity) {
	dispatchToCommonStore(setTicketsSharedCapacity(capacity));
	dispatchToCommonStore(setTicketsTempSharedCapacity(capacity));
	setTicketHasChangesInCommonStore(clientId);
}

/**
 * Sets the capacity in the common store.
 *
 * @since 5.16.0
 *
 * @param {string} clientId The client ID of the current ticket block.
 * @param {number} capacity The capacity to set.
 */
export function setCappedTicketCapacityInCommonStore(clientId, capacity) {
	dispatchToCommonStore(setTicketCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketTempCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketCapacityType(clientId, CAPPED));
	dispatchToCommonStore(setTicketTempCapacityType(clientId, CAPPED));
	setTicketHasChangesInCommonStore(clientId);
}

/**
 * Sets the has changes flag in the common store.
 *
 * @since 5.16.0
 *
 * @param {string} clientId The client ID of the Ticket block to update.
 */
export function setTicketHasChangesInCommonStore(clientId) {
	dispatchToCommonStore(setTicketHasChanges(clientId, true));
}

/**
 * Returns the ticket post ID fetched from the common store.
 *
 * @since 5.16.0
 *
 * @param {string} clientId The client ID of the Ticket block to update.
 *
 * @return {string} The ticket ID.
 */
export function getTicketIdFromCommonStore(clientId) {
	return selectFromCommonStore(getTicketId, { clientId });
}

/**
 * Returns the shared capacity integer value fetched from the common store.
 *
 * @since 5.16.0
 *
 *
 * @return {number} The current integer value of the shared capacity.
 */
export function getTicketsSharedCapacityFromCommonStore() {
	return selectFromCommonStore(getTicketsSharedCapacityInt);
}

/**
 * Returns the current Ticket provider fetched from the Common store.
 *
 * @since 5.16.0
 *
 * @return {string} The current ticket Provider fetched from the Common store,
 *                  or an empty string if the Ticket block client ID is not set.
 */
export function getTicketProviderFromCommonStore() {
	try {
		return selectFromCommonStore(getTicketsProvider);
	} catch (e) {
		return '';
	}
}
