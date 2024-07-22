import {
	setTicketCapacity,
	setTicketCapacityType,
	setTicketsSharedCapacity,
	setTicketsTempSharedCapacity,
	setTicketTempCapacity,
	setTicketTempCapacityType,
} from '@moderntribe/tickets/data/blocks/ticket/actions';
import { getTicketId } from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { SHARED } from '@moderntribe/tickets/data/blocks/ticket/constants';

function dispatchToCommonStore(action) {
	window.__tribe_common_store__.dispatch(action);
}

function selectFromCommonStore(selector, ...args) {
	return selector(window.__tribe_common_store__.getState(), ...args);
}

export function setTicketsSharedCapacityInCommonStore(capacity) {
	dispatchToCommonStore(setTicketsSharedCapacity(capacity));
	dispatchToCommonStore(setTicketsTempSharedCapacity(capacity));
}

export function getTicketIdFromCommonStore(clientId) {
	return selectFromCommonStore(getTicketId, { clientId });
}

export function setCappedTicketCapacityInCommonStore(clientId, capacity) {
	dispatchToCommonStore(setTicketCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketTempCapacity(clientId, capacity));
	dispatchToCommonStore(setTicketCapacityType(clientId, SHARED));
	dispatchToCommonStore(setTicketTempCapacityType(clientId, SHARED));
}
