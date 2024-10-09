import {
	setTicketCapacity,
	setTicketCapacityType,
	setTicketsSharedCapacity,
	setTicketsTempSharedCapacity,
	setTicketTempCapacity,
	setTicketTempCapacityType,
	setTicketHasChanges,
} from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	getTicketId,
} from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { CAPPED } from '@moderntribe/tickets/data/blocks/ticket/constants';

function dispatchToCommonStore(action) {
	window.__tribe_common_store__.dispatch(action);
}

function selectFromCommonStore(selector, ...args) {
	return selector(window.__tribe_common_store__.getState(), ...args);
}

export function setTicketsSharedCapacityInCommonStore(capacity, clientId) {
	dispatchToCommonStore(setTicketsSharedCapacity(capacity));
	dispatchToCommonStore(setTicketsTempSharedCapacity(capacity));
	setTicketHasChangesInCommonStore(clientId);
}

export function getTicketIdFromCommonStore(clientId) {
	return selectFromCommonStore(getTicketId, { clientId });
}

export function setCappedTicketCapacityInCommonStore(clientId, capacity) {
	dispatchToCommonStore(setTicketCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketTempCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketCapacityType(clientId, CAPPED));
	dispatchToCommonStore(setTicketTempCapacityType(clientId, CAPPED));
	setTicketHasChangesInCommonStore(clientId);
}

export function setTicketHasChangesInCommonStore(clientId) {
	dispatchToCommonStore(setTicketHasChanges(clientId, true));
}
