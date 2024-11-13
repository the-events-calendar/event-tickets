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
export const filterSetBodyDetails = ( body, clientId ) => {
	const feesSelected = select( storeName ).getSelectedFees( clientId );

	body.append( 'ticket[fees][selected_fees]', feesSelected );

	return body;
};
