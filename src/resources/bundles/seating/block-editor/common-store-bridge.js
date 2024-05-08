import {
	setTicketCapacity,
	setTicketCapacityType,
	setTicketsSharedCapacity,
	setTicketsTempSharedCapacity,
	setTicketTempCapacity,
	setTicketTempCapacityType,
} from '@moderntribe/tickets/data/blocks/ticket/actions';
import { getTicketId } from '@moderntribe/tickets/data/blocks/ticket/selectors';

function dispatchToCommonStore(action) {
	window.__tribe_common_store__.dispatch(action);
}

function selectFromCommonStore(selector, ...args) {
	return selector(window.__tribe_common_store__.getState(), ...args);
}

export function setTicketsSharedCapacityInCommonStore(capacity) {
	dispatchToCommonStore(setTicketsSharedCapacity(capacity));
}

export function setTicketsTempSharedCapacityInCommonStore(capacity) {
	dispatchToCommonStore(setTicketsTempSharedCapacity(capacity));
}

export function setTicketCapacityInCommonStore(ticketId, capacity) {
	dispatchToCommonStore(setTicketCapacity(ticketId, capacity));
}

export function setTicketTempCapacityInCommonStore(ticketId, capacity) {
	dispatchToCommonStore(setTicketTempCapacity(ticketId, capacity));
}

export function setTicketCapacityTypeInCommonStore(ticketId, capacityType) {
	dispatchToCommonStore(setTicketCapacityType(ticketId, capacityType));
}

export function setTicketTempCapacityTypeInCommonStore(ticketId, capacityType) {
	dispatchToCommonStore(setTicketTempCapacityType(ticketId, capacityType));
}

export function getTicketIdFromCommonStore(clientId) {
	return selectFromCommonStore(getTicketId, { clientId });
}
