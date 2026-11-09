/**
 * The REST body a Ticket block's create or update sends.
 *
 * Kept apart from the ticket sagas so the deferred sagas can build a body without importing them.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { call, select } from 'redux-saga/effects';

/**
 * WordPress dependencies
 */
import { select as wpSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import * as selectors from './selectors';

const { UNLIMITED, SHARED, TICKET_TYPES } = constants;

/**
 * Returns a sale price date as the ticket request sends it.
 *
 * The store keeps an empty sale price date it loads or saves as the string `Invalid date`, which the server rejects
 * as a date it cannot read; it is sent empty, as it was when the ticket was created.
 *
 * @since 5.31.0
 *
 * @param {string} date The sale price date the store holds.
 *
 * @return {string} The date, `YYYY-MM-DD`, or an empty string.
 */
export function toRequestSaleDate( date ) {
	return 'string' === typeof date && constants.SALE_DATE_PATTERN.test( date ) ? date : '';
}

export function* setBodyDetails( clientId ) {
	let body = new FormData();
	const props = { clientId };
	const rootClientId = yield call( [ wpSelect( 'core/block-editor' ), 'getBlockRootClientId' ], clientId );
	const ticketProvider = yield select( selectors.getTicketProvider, props );
	const ticketsProvider = yield select( selectors.getTicketsProvider );

	body.append( 'post_id', yield call( [ wpSelect( 'core/editor' ), 'getCurrentPostId' ] ) );
	body.append( 'provider', ticketProvider || ticketsProvider );
	body.append( 'name', yield select( selectors.getTicketTempTitle, props ) );
	body.append( 'description', yield select( selectors.getTicketTempDescription, props ) );
	body.append( 'price', yield select( selectors.getTicketTempPrice, props ) );
	body.append( 'start_date', yield select( selectors.getTicketTempStartDate, props ) );
	body.append( 'start_time', yield select( selectors.getTicketTempStartTime, props ) );
	body.append( 'end_date', yield select( selectors.getTicketTempEndDate, props ) );
	body.append( 'end_time', yield select( selectors.getTicketTempEndTime, props ) );
	body.append( 'sku', yield select( selectors.getTicketTempSku, props ) );
	body.append( 'iac', yield select( selectors.getTicketTempIACSetting, props ) );
	body.append(
		'menu_order',
		yield call( [ wpSelect( 'core/block-editor' ), 'getBlockIndex' ], clientId, rootClientId )
	);

	const capacityType = yield select( selectors.getTicketTempCapacityType, props );
	const capacity = yield select( selectors.getTicketTempCapacity, props );

	const isUnlimited = capacityType === TICKET_TYPES[ UNLIMITED ];
	body.append( 'ticket[mode]', isUnlimited ? '' : capacityType );
	body.append( 'ticket[capacity]', isUnlimited ? '' : capacity );

	if ( capacityType === TICKET_TYPES[ SHARED ] ) {
		body.append( 'ticket[event_capacity]', yield select( selectors.getTicketsTempSharedCapacity ) );
	}

	const showSalePrice = yield select( selectors.showSalePrice, props );

	if ( showSalePrice ) {
		body.append( 'ticket[sale_price][checked]', yield select( selectors.getTempSalePriceChecked, props ) );
		body.append( 'ticket[sale_price][price]', yield select( selectors.getTempSalePrice, props ) );
		body.append(
			'ticket[sale_price][start_date]',
			toRequestSaleDate( yield select( selectors.getTicketTempSaleStartDate, props ) )
		);
		body.append(
			'ticket[sale_price][end_date]',
			toRequestSaleDate( yield select( selectors.getTicketTempSaleEndDate, props ) )
		);
	}

	/**
	 * Fires after the body details have been set and before the request is sent.
	 * The action will fire both when a ticket is being created and when an existing ticket is being updated.
	 *
	 * @since 5.16.0
	 * @param {Object} body     The body of the request.
	 * @param {string} clientId The client ID of the ticket block that is being created or updated.
	 */
	body = applyFilters( 'tec.tickets.blocks.setBodyDetails', body, clientId );

	return body;
}
