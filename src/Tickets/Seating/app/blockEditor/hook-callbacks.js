import { storeName } from './store';
import { select, dispatch } from '@wordpress/data';
import SeatType from './header/seat-type';
import LayoutSelect from "./settings/layoutSelect";

export const setSeatTypeForTicket = (clientId) =>
	dispatch(storeName).setTicketSeatTypeByPostId(clientId);

/**
 * Filters whether the ticket is ASC.
 *
 * @since TBD
 *
 * @param {boolean} isAsc    Whether ticket is ASC.
 * @param {number}  clientId The ticket ID.
 *
 * @return {boolean} Whether ticket is ASC.
 */
export const filterTicketIsAsc = (isAsc, clientId) => {
	return isAsc || !!select(storeName).getTicketSeatType(clientId);
};

/**
 * Filters the header details of the ticket to add the seating type name.
 *
 * @since TBD
 *
 * @param {Array}  items    The header details of the ticket.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Array} The header details.
 */
export const filterHeaderDetails = (items, clientId) => {
	const hasSeats = select(storeName).isUsingAssignedSeating(clientId);
	if (!hasSeats) {
		return items;
	}
	const seatTypeId = select(storeName).getTicketSeatType(clientId);
	const seatTypes = select(storeName).getSeatTypesForLayout(
		select(storeName).getCurrentLayoutId(),
		true
	);

	const seatTypeName = Object.values(seatTypes).find(
		(seatType) => seatType.id === seatTypeId
	)?.name;

	if (seatTypeName) {
		items.push(<SeatType name={seatTypeName} />);
	}

	return items;
};

/**
 * Filters the body details of the ticket to add the seating details.
 *
 * @since TBD
 *
 * @param {Object} body     The body of the request.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Object} The body of the request with the seating details.
 */
export const filterSetBodyDetails = (body, clientId) => {
	/**
	 * On first save of a ticket, lock the Layout.
	 * Doesn't matter if ASC or GAC, they layout should be locked.
	 */
	dispatch(storeName).setIsLayoutLocked(true);

	const layoutId = select(storeName).getCurrentLayoutId();
	if (!layoutId) {
		return body;
	}

	const seatType = select(storeName).getTicketSeatType(clientId);
	const eventCapacity = select(storeName).getEventCapacity();
	body.append('ticket[seating][enabled]', seatType ? '1' : '0');
	body.append('ticket[seating][seatType]', seatType ? seatType : '');
	body.append('ticket[seating][layoutId]', layoutId);
	body.append('ticket[event_capacity]', eventCapacity);

	return body;
};

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

/**
 * Filters the settings fields to include the layout selection.
 *
 * @since TBD
 *
 * @param {Array} fields The settings fields.
 *
 * @return {Array} The settings fields.
 */
export const filterSettingsFields = (fields) => {
	const store = select(storeName);
	const currentLayout = store.getCurrentLayoutId();
	const layouts = store.getLayoutsInOptionFormat();

	fields.push(
		<LayoutSelect layouts={layouts} currentLayout={currentLayout} />
	);

	return fields;
};

/**
 * Filters whether the confirm save button is disabled.
 *
 * @since TBD
 *
 * @param {boolean} isDisabled Whether the button is disabled.
 * @param {Object}  state      The state of the store.
 * @param {Object}  ownProps   The own props of the component.
 *
 * @return {boolean} Whether the button is disabled.
 */
export const filterButtonIsDisabled = (isDisabled, state, ownProps) => {
	if (isDisabled) {
		// If disabled already, we have no reason to enable it.
		return isDisabled;
	}

	const store = select(storeName);

	if (!store.isUsingAssignedSeating()) {
		return isDisabled;
	}

	if (!store.getCurrentLayoutId()) {
		return true;
	}

	if (!store.getTicketSeatType(ownProps.clientId)) {
		return true;
	}

	return false;
};
