/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { getSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import {
	getTicketFormDates,
	isTicketReadyBesidesDuration,
	isTicketsCommerce,
	readTicketFormDates,
	TICKETS_COMMERCE_PROVIDER,
} from './common-store-bridge';
import { readEventDates } from './event-dates';
import { MODE_DEFAULT, MODE_RELATIVE } from '../rule-constants';
import { getFormRule, isSpecificWindow } from './rule';
import { formatSaleDate, resolveTicketWindow } from './sale-dates';
import SalesWindow from './sales-window';
import { STORE_NAME } from './store/constants';
import { getTicketWindowError } from './window-error';

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
 * Returns whether a ticket's draft rule gives a sales window the server rejects, judged against the event dates in the
 * editor.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null} rule     The ticket's draft rule.
 * @param {string}              clientId The client ID of the ticket block.
 *
 * @return {boolean} Whether the window is invalid; `false` without the event dates to judge it by.
 */
function hasWindowError( rule, clientId ) {
	const eventDates = readEventDates();

	return Boolean( eventDates ) && null !== getTicketWindowError( rule, eventDates, readTicketFormDates( clientId ) );
}

/**
 * Loads the rule of a Tickets Commerce ticket fetched from the server.
 *
 * The store knows nothing of a ticket another provider sells, so its requests carry no rule.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} ticket   The ticket, as the block editor tickets REST API returns it.
 *
 * @return {void}
 */
export function loadTicketRule( clientId, ticket ) {
	if ( TICKETS_COMMERCE_PROVIDER !== ticket?.provider ) {
		return;
	}

	dispatch( STORE_NAME ).setRule( clientId, ticket.relative_sale_dates ?? null );
}

/**
 * Keeps the rule a ticket was just created or updated with as its saved rule.
 *
 * Only the rule the request carried counts: a draft held back from it was never stored, so Cancel still goes back to
 * the rule the ticket has.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {void}
 */
export function saveTicketRule( clientId ) {
	dispatch( STORE_NAME ).saveSentRule( clientId );
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
 * sends an empty one, which removes it. A draft whose window is invalid (it does not start before it ends, or a relative
 * start or end has no number) sends no rule either: saving the post updates the ticket too, and the server would reject
 * the ticket's other changes with it.
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

	if ( hasWindowError( rule, clientId ) ) {
		dispatch( STORE_NAME ).setSentRule( clientId, undefined );

		return body;
	}

	const value = rule ? JSON.stringify( { start: toRequestEnd( rule.start ), end: toRequestEnd( rule.end ) } ) : '';
	body.append( 'ticket[relative_sale_dates]', value );
	dispatch( STORE_NAME ).setSentRule( clientId, rule );

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
	if ( ! isTicketsCommerce( clientId ) ) {
		return picker;
	}

	return <SalesWindow clientId={ clientId } picker={ picker } />;
}

/**
 * Shows the dates a ticket's rule works out to in the sale window of the ticket header.
 *
 * A start of `Now` or a specific date, and a specific end, keep the ticket's own date.
 *
 * @since TBD
 *
 * @param {{fromDate: string, toDate: string}} dates    The sale dates the header shows, in the site date format.
 * @param {string}                             clientId The client ID of the ticket block.
 *
 * @return {{fromDate: string, toDate: string}} The sale dates to show.
 */
export function filterSaleWindowDates( dates, clientId ) {
	const saleWindow = resolveTicketWindow( select( STORE_NAME ).getDraftRule( clientId ), readEventDates() );

	if ( ! saleWindow ) {
		return dates;
	}

	const { date: dateFormat } = getSettings().formats;
	const format = ( date, ticketDate ) => ( date?.isValid() ? formatSaleDate( dateFormat, date ) : ticketDate );

	return { fromDate: format( saleWindow.start, dates.fromDate ), toDate: format( saleWindow.end, dates.toDate ) };
}

/**
 * Keeps a ticket from being created or updated while its sales window is invalid: it does not start before it ends, or
 * a relative start or end has no number.
 *
 * The legacy sales duration error alone does not keep the button disabled once an end is relative or the default, since
 * the dates it checks are hidden then.
 *
 * @since TBD
 *
 * @param {boolean} isDisabled        Whether the Create or Update button is disabled already.
 * @param {Object}  state             The legacy ticket state.
 * @param {Object}  ownProps          The props of the ticket block's dashboard.
 * @param {string}  ownProps.clientId The client ID of the ticket block.
 *
 * @return {boolean} Whether the button is disabled.
 */
export function filterConfirmDisabled( isDisabled, state, { clientId } ) {
	if ( ! isTicketsCommerce( clientId ) ) {
		return isDisabled;
	}

	const rule = select( STORE_NAME ).getDraftRule( clientId );

	// The legacy duration error judges the picker's dates, which mean nothing once an end is relative or the default.
	const onlyDurationError =
		! isSpecificWindow( getFormRule( rule ) ) && isTicketReadyBesidesDuration( state, clientId );

	if ( isDisabled && ! onlyDurationError ) {
		return true;
	}

	const error = getTicketWindowError( rule, readEventDates(), getTicketFormDates( state, clientId ) );

	return null !== error;
}

/**
 * Stops the legacy sync from moving a ticket's sale end to the event start when its rule sets another end: a relative
 * one, or the ticket's own specific date. An end of *When the event starts* follows the event start, as the server
 * resolves it.
 *
 * @since TBD
 *
 * @param {boolean} followsEventStart Whether the sale end follows the event start.
 * @param {string}  clientId          The client ID of the ticket block.
 *
 * @return {boolean} Whether the sale end follows the event start.
 */
export function filterSyncSaleEndWithEventStart( followsEventStart, clientId ) {
	const rule = select( STORE_NAME ).getDraftRule( clientId );

	return rule && MODE_DEFAULT !== rule.end.mode ? false : followsEventStart;
}
