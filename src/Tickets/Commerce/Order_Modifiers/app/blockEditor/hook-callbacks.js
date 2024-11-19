/**
 * External dependencies.
 */
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import Fees from "./fees";
import { storeName } from './store';
import { currentProviderSupportsFees } from './store/compatibility';

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

/**
 * Filters the ticket container items.
 *
 * @since TBD
 *
 * @param {Array}  items    The ticket container items.
 * @param {string} clientId The client ID.
 * @return {Array} The filtered ticket container items.
 */
export const filterTicketContainerItems = ( items, clientId ) => {
	// Don't add fees if the provider doesn't support them.
	if ( ! currentProviderSupportsFees() ) {
		return items;
	}

	// @todo: put this in the correct place in the array of items.
	items.push( <Fees clientId={ clientId }/> );

	return items;
}
