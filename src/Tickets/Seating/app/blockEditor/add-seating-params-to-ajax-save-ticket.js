import { storeName } from './store';
import { select, dispatch } from '@wordpress/data';

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

	// On first save of a ticket, lock the Layout.
	dispatch(storeName).setIsLayoutLocked(true);

	return body;
};
