import { storeName } from './store';
import { select } from '@wordpress/data';

/**
 * Modifies the properties mapped from the state for the Availability component to conform
 * to the Assigned Seating feature.
 *
 * @since TBD
 *
 * @param {Object} mappedProps           The properties mapped from the state for the Availability component.
 * @param {number} mappedProps.total     The total capacity.
 * @param {number} mappedProps.available The available capacity.
 */
export const filterSeatedTicketsAvailabilityMappedProps = (mappedProps) => {
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
	const activeSeatsByClient = Object.values(store.getSeatTypesByClientID());
	const activeSeatsByPost = Object.values(store.getSeatTypesByPostID());
	const activeSeatTypes =
		activeSeatsByPost.length > activeSeatsByClient.length
			? activeSeatsByPost
			: activeSeatsByClient;

	const activeSeatTypesFiltered = activeSeatTypes.filter(
		(value, index, array) => array.indexOf(value) === index
	);

	const activeSeatTypeTotalCapacity = activeSeatTypesFiltered.reduce(
		(sum, type) => sum + parseInt(seatTypes[type] ? seatTypes[type].seats : 0),
		0
	);

	const seatTypeTotalCapacity = Object.values(seatTypes).reduce(
		(sum, { seats }) => sum + parseInt(seats),
		0
	);

	const soldAndPending = Math.abs(
		parseInt(mappedProps?.total || 0) -
			parseInt(mappedProps?.available || 0)
	);

	return {
		total: seatTypeTotalCapacity,
		available: Math.abs(activeSeatTypeTotalCapacity - soldAndPending),
	};
};
