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
	const ticketId = selectFromCommonStore(getTicketId, {clientId});
	dispatchToCommonStore(setTicketCapacity(ticketId, capacity));
	dispatchToCommonStore(setTicketTempCapacity(ticketId, capacity));
	dispatchToCommonStore(setTicketCapacityType(ticketId, SHARED));
	dispatchToCommonStore(setTicketTempCapacityType(ticketId, SHARED));
}
