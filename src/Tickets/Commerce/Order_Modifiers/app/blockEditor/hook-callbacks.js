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
 * @since 5.18.0
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
 * @since 5.18.0
 *
 * @param {object[]}  items    The ticket container items.
 * @param {string} clientId The client ID.
 * @return {object[]} The filtered ticket container items.
 */
export const filterTicketContainerItems = ( items, clientId ) => {
	// Don't add fees if the provider doesn't support them.
	if ( ! currentProviderSupportsFees() ) {
		return items;
	}

	// Define our fee object.
	const feeObject = {
		item: <Fees clientId={ clientId } />,
		key: 'fees',
	};

	// Add the fee object after the "duration" item.
	const durationIndex = items.findIndex( ( item ) => item.key === 'duration' );
	items.splice( durationIndex + 1, 0, feeObject );

	return items;
}

/**
 * Sets the fees for the ticket.
 *
 * @since 5.18.0
 *
 * @param {string} clientId The client ID of the ticket block.
 * @param {object} ticket   The ticket object.
 */
export const setFeesForTicket = ( clientId, ticket ) => {
	// Get the fees from the ticket object.
	const { fees } = ticket;
	if ( ! fees ) {
		return;
	}

	const feesSelected = fees?.selected_fees || [];

	dispatch( storeName ).setTicketFees( clientId, feesSelected );
}

/**
 * Updates the fees for the ticket.
 *
 * @since 5.18.0
 *
 * @param {string} clientId The client ID of the ticket block.
 */
export const updateFeesForTicket = ( clientId ) => {
	dispatch( storeName ).setFeesByPostId( clientId );
}
