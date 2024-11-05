import { select } from '@wordpress/data';
import { storeName } from './store';
import { getLocalizedString } from '@tec/tickets/seating/utils';

/**
 * Filters the mapped props for the Capacity Table component.
 *
 *
 * @since 5.16.0
 *
 * @param {Object} mappedProps The mapped props for the Capacity Table component.
 *
 * @return {Object} The mapped props for the Capacity Table component.
 */
export function filterCapacityTableMappedProps(mappedProps) {
	const store = select(storeName);
	const hasSeats = store.isUsingAssignedSeating();
	const layoutLocked = store.isLayoutLocked();

	if (!(hasSeats && layoutLocked)) {
		return mappedProps;
	}

	const layoutId = store.getCurrentLayoutId();
	if (!layoutId) {
		return mappedProps;
	}

	const seatTypes = store.getSeatTypesForLayout(layoutId, true);
	const activeSeatTypes = Object.values(store.getSeatTypesByPostID()).filter(
		(value, index, array) => array.indexOf(value) === index
	);

	if (!Object.keys(seatTypes).length || !activeSeatTypes.length) {
		return mappedProps;
	}

	mappedProps.rowsAfter = mappedProps.rowsAfter || [];
	const seatTypeLabels = activeSeatTypes.map((type) => seatTypes[type].name);
	const seatTypeTotalCapacity = activeSeatTypes.reduce(
		(sum, type) => sum + parseInt(seatTypes[type].seats),
		0
	);
	mappedProps.rowsAfter.push({
		label: getLocalizedString('seats-row-label', 'capacity-table'),
		items: seatTypeLabels ? `(${seatTypeLabels})` : '',
		right: String(seatTypeTotalCapacity),
	});

	mappedProps.totalCapacity =
		mappedProps.totalCapacity -
		mappedProps.sharedCapacity +
		seatTypeTotalCapacity;
	mappedProps.sharedCapacity = '';
	mappedProps.sharedTicketItems = '';

	return mappedProps;
}
