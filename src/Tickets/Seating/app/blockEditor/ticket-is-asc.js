import { storeName } from './store';
import { select } from '@wordpress/data';

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
