/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isTicketsCommerce } from './common-store-bridge';
import { MODE_RELATIVE } from '../rule-constants';
import SalesWindow from './sales-window';
import { STORE_NAME } from './store/constants';

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * Builds the form of one end of the window the server accepts.
 *
 * The server only accepts a relative `value` and `unit` that are integers, and form controls hold strings.
 *
 * @since TBD
 *
 * @param {SaleWindowEnd} end The end of the window.
 *
 * @return {SaleWindowEnd} The end of the window, with only the keys its mode uses.
 */
function toRequestEnd( { mode, value, unit, anchor } ) {
	if ( MODE_RELATIVE !== mode ) {
		return { mode };
	}

	return {
		mode,
		value: parseInt( value, 10 ),
		unit: parseInt( unit, 10 ),
		anchor,
	};
}

/**
 * Loads the rule of a ticket fetched from the server.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} ticket   The ticket, as the block editor tickets REST API returns it.
 *
 * @return {void}
 */
export function loadTicketRule( clientId, ticket ) {
	dispatch( STORE_NAME ).setRule( clientId, ticket?.relative_sale_dates ?? null );
}

/**
 * Keeps the rule a ticket was just created or updated with as its saved rule.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function saveTicketRule( clientId ) {
	dispatch( STORE_NAME ).saveDraftRule( clientId );
}

/**
 * Discards the rule a ticket block was being edited to when its edits are cancelled.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function resetTicketRule( clientId ) {
	dispatch( STORE_NAME ).resetDraftRule( clientId );
}

/**
 * Adds the ticket's draft rule to the body of the request that creates or updates it.
 *
 * A ticket the store knows nothing of sends no rule, so the server keeps the one stored; one whose draft has no rule
 * sends an empty one, which removes it.
 *
 * @since TBD
 *
 * @param {FormData} body     The request body.
 * @param {string}   clientId The client ID of the ticket block.
 *
 * @return {FormData} The request body.
 */
export function filterSetBodyDetails( body, clientId ) {
	/** @type {SaleWindowRule|null|undefined} */
	const rule = select( STORE_NAME ).getDraftRule( clientId );

	if ( undefined === rule ) {
		return body;
	}

	const value = rule ? JSON.stringify( { start: toRequestEnd( rule.start ), end: toRequestEnd( rule.end ) } ) : '';
	body.append( 'ticket[relative_sale_dates]', value );

	return body;
}

/**
 * Renders the sales window options of a Tickets Commerce ticket in place of its Sale Duration picker.
 *
 * @since TBD
 *
 * @param {Object} picker   The date and time range picker element.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Object} The sales window options, or the picker for a ticket another provider sells.
 */
export function filterTicketDuration( picker, clientId ) {
	if ( ! isTicketsCommerce() ) {
		return picker;
	}

	return <SalesWindow clientId={ clientId } picker={ picker } />;
}
