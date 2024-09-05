import { storeName } from './store';
import { select } from '@wordpress/data';
import SeatType from './header/seat-type';

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
