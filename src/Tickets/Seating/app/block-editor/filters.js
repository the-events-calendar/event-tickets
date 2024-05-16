import { addFilter } from '@wordpress/hooks';
import CapacityForm from './capacity-form';
import { storeName } from './store';
import { select, dispatch } from '@wordpress/data';

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
	body.append('ticket[seating][enabled]', seatType ? '1' : '0');
	body.append('ticket[seating][seatType]', seatType);

	// On first save of a ticket, lock the Layout.
	dispatch(storeName).setIsLayoutLocked(true);

	return body;
}

addFilter(
	'tec.tickets.blocks.setBodyDetails',
	'tec.tickets.seating',
	filterSetBodyDetails
);
