import { addFilter } from '@wordpress/hooks';
import CapacityForm from './capacity-form';
import { storeName } from './store';
import { select, dispatch } from '@wordpress/data';
import Seats from './dashboard-actions/seats';
import SeatType from './header/seat-type';
import { filterCapacityTableMappedProps } from './capacity-table';
import { filterSeatedTicketsAvailabilityMappedProps } from './availability-overview';
import LayoutSelect from "./settings/layoutSelect";

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

addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.seating',
	filterSeatedTicketsAvailabilityMappedProps
);

function filterSettingsFields(fields) {
	const store = select(storeName);
	const hasSeats = store.isUsingAssignedSeating();
	const layoutLocked = store.isLayoutLocked();

	if ( ! hasSeats || ! layoutLocked ) {
		return fields;
	}

	const currentLayout = select(storeName).getCurrentLayoutId();
	const layouts = select(storeName).getLayoutsInOptionFormat()

	if ( ! currentLayout || layouts.length === 0 ) {
		return fields;
	}

	fields.push(
		<LayoutSelect
			layouts={layouts}
			currentLayout={currentLayout}
		/>
	);

	return fields;
}

addFilter(
	'tec.tickets.blocks.Tickets.Settings.Fields',
	'tec.tickets.seating',
	filterSettingsFields
);
