import { addFilter } from '@wordpress/hooks';
import CapacityForm from './capacity-form';
import { storeName } from './store';
import { select, dispatch } from '@wordpress/data';
import Seats from './dashboard-actions/seats';
import SeatType from './header/seat-type';
import { filterCapacityTableMappedProps } from './capacity-table';

const shouldRenderAssignedSeatingForm = true;

/**
 * Filters the render function of the Capacity form to add the seating options.
 *
 * @param {function(): void} renderDefaultForm The render function of the Capacity form.k
 * @param {string }          clientId          The client ID of the ticket block.
 *
 * @return {Function} The render function of the Capacity form with the seating options.
 */
function filterRenderCapacityForm(renderDefaultForm, { clientId }) {
	if (!shouldRenderAssignedSeatingForm) {
		return renderDefaultForm;
	}

	return () => (
		<CapacityForm
			renderDefaultForm={renderDefaultForm}
			clientId={clientId}
		/>
	);
}

addFilter(
	'tec.tickets.blocks.Ticket.Capacity.renderForm',
	'tec.tickets.seating',
	filterRenderCapacityForm
);

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
function filterSetBodyDetails(body, clientId) {
	const seatType = select(storeName).getTicketSeatType(clientId);
	const eventCapacity = select(storeName).getEventCapacity();
	const layoutId = select(storeName).getCurrentLayoutId();
	body.append('ticket[seating][enabled]', seatType ? '1' : '0');
	body.append('ticket[seating][seatType]', seatType);
	body.append('ticket[seating][layoutId]', layoutId);
	body.append('ticket[event_capacity]', eventCapacity);

	// On first save of a ticket, lock the Layout.
	dispatch(storeName).setIsLayoutLocked(true);

	return body;
}

addFilter(
	'tec.tickets.blocks.setBodyDetails',
	'tec.tickets.seating',
	filterSetBodyDetails
);

/**
 * Filters the action items of the dashboard to add the seating actions.
 *
 * @since TBD
 *
 * @param {Array}  actions  The action items of the dashboard.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Array} The action items.
 */
function filterDashboardActions(actions, { clientId }) {
	const hasSeats = select(storeName).isUsingAssignedSeating(clientId);
	const layoutLocked = select(storeName).isLayoutLocked();

	// Only show if there are seats and the post is saved.
	if (hasSeats && layoutLocked) {
		actions.push(<Seats />);
	}

	return actions;
}

addFilter(
	'tec.tickets.blocks.Tickets.TicketsDashboardAction.actions',
	'tec.tickets.seating',
	filterDashboardActions
);

/**
 * Filters the ticket edit action items to remove the move button for seated tickets.
 *
 * @since TBD
 *
 * @param {Object[]} actions  The action items of the ticket.
 * @param {string}   clientId The client ID of the ticket block.
 *
 * @return {Array} The action items.
 */
function filterMoveButtonAction(actions, clientId) {
	const hasSeats = select(storeName).isUsingAssignedSeating(clientId);
	if (!hasSeats) {
		return actions;
	}

	return actions.filter((action) => action.key !== 'move');
}

addFilter(
	'tec.tickets.blocks.Ticket.actionItems',
	'tec.tickets.seating',
	filterMoveButtonAction
);

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
function filterHeaderDetails(items, clientId) {
	const hasSeats = select(storeName).isUsingAssignedSeating(clientId);
	if (!hasSeats) {
		return items;
	}

	const seatTypeId = select(storeName).getTicketSeatType(clientId);
	const seatTypes = select(storeName).getAllSeatTypes();

	const seatTypeName = Object.values(seatTypes).find(
		(seatType) => seatType.id === seatTypeId
	)?.name;

	if (seatTypeName) {
		items.push(<SeatType name={seatTypeName} />);
	}

	return items;
}

addFilter(
	'tec.tickets.blocks.Ticket.header.detailItems',
	'tec.tickets.seating',
	filterHeaderDetails
);

addFilter(
	'tec.tickets.blocks.Tickets.CapacityTable.mappedProps',
	'tec.tickets.flexibleTickets',
	filterCapacityTableMappedProps
);

/**
 * Modifies the properties mapped from the state for the Availability component to conform
 * to the Flexible Tickets feature.
 *
 * @since 5.8.0
 *
 * @param {Object} mappedProps           The properties mapped from the state for the Availability component.
 * @param {number} mappedProps.total     The total capacity.
 * @param {number} mappedProps.available The available capacity.
 */
function filterSeatedTicketsAvailabilityMappedProps(mappedProps) {
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
		(sum, type) => sum + parseInt(seatTypes[type].seats),
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
}

addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.seating',
	filterSeatedTicketsAvailabilityMappedProps
);
