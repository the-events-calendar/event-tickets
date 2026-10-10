/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { getSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { isTicketReadyBesidesDuration, isTicketsCommerce, TICKETS_COMMERCE_PROVIDER } from './common-store-bridge';
import { readEventDates } from './event-dates';
import { MODE_DEFAULT } from '../rule-constants';
import { getFormRule, isSpecificWindow, toRequestRule } from './rule';
import { formatSaleDate, resolveTicketWindow } from './sale-dates';
import SalePriceWindow from './sale-price-window';
import SalesWindow from './sales-window';
import { STORE_NAME } from './store/constants';
import {
	getTicketWindowError,
	getTicketWindowReader,
	hasTicketWindowSaveError,
	readTicketWindowForm,
} from './window-error';
import { BLOCK_WINDOW_KINDS } from './window-kinds';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */

/**
 * Loads the rules of a Tickets Commerce ticket fetched from the server.
 *
 * The store knows nothing of a ticket another provider sells, so its requests carry no rule. A ticket without a sale
 * price has no sale price rule to load, so checking its sale price opens on the defaults.
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

	BLOCK_WINDOW_KINDS.forEach( ( kind ) =>
		dispatch( STORE_NAME ).setRule( clientId, kind.readStored( ticket ), kind )
	);
}

/**
 * Keeps the rules a ticket was just created or updated with as its saved rules.
 *
 * The server's answer is what was stored: a later save can have changed what the store last sent before this one's
 * answer arrives. Without an answer to read, only the rule the request carried counts: a draft held back from it was
 * never stored, so Cancel still goes back to the rule the ticket has.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 * @param {number} ticketId The ticket ID.
 * @param {Object} details  The ticket details.
 * @param {Object} [ticket] The ticket, as the tickets REST API answered the save.
 *
 * @return {void}
 */
export function saveTicketRule( clientId, ticketId, details, ticket ) {
	dispatch( STORE_NAME ).saveSentRule( clientId );

	if ( TICKETS_COMMERCE_PROVIDER !== ticket?.provider ) {
		return;
	}

	BLOCK_WINDOW_KINDS.filter( ( kind ) => kind.isAnswered( ticket ) ).forEach( ( kind ) =>
		dispatch( STORE_NAME ).saveConfirmedRule( clientId, kind.readStored( ticket ), kind )
	);
}

/**
 * Discards the rules a ticket block was being edited to when its edits are cancelled.
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
 * Adds one window's draft rule to the body of the request that creates or updates a ticket, and records what it
 * carried.
 *
 * A ticket the store knows nothing of sends no rule, so the server keeps the one stored; one whose draft has no rule
 * sends the kind's `emptyValue`. A draft the editor judges the server would reject sends no rule either, nor does one
 * whose parent window is rejected: saving the post updates the ticket too, and the server would reject the ticket's
 * other changes with it. Nor does the rule of a window the ticket form does not add.
 *
 * @since TBD
 *
 * @param {FormData}                      body     The request body.
 * @param {string}                        clientId The client ID of the ticket block.
 * @param {BlockWindowKind}               kind     The window kind.
 * @param {SaleWindowRule|null|undefined} rule     The ticket's draft rule of the window.
 *
 * @return {void}
 */
export function appendRule( body, clientId, kind, rule ) {
	if ( undefined === rule ) {
		return;
	}

	const value = rule ? JSON.stringify( toRequestRule( rule, kind ) ) : kind.emptyValue;
	const readWindow = getTicketWindowReader( clientId, select, ( each ) => readTicketWindowForm( clientId, each ) );
	const isSent =
		undefined !== value &&
		kind.isAdded( clientId ) &&
		! hasTicketWindowSaveError( kind, readWindow, readEventDates() );

	if ( isSent ) {
		body.append( kind.requestKey, value );
	}

	dispatch( STORE_NAME ).setSentRule( clientId, isSent ? rule : undefined, kind );
}

/**
 * Adds the ticket's draft rules to the body of the request that creates or updates it, the sales window first.
 *
 * @since TBD
 *
 * @param {FormData} body     The request body.
 * @param {string}   clientId The client ID of the ticket block.
 *
 * @return {FormData} The request body.
 */
export function filterSetBodyDetails( body, clientId ) {
	// The options are hidden for another provider, whose ticket the server saves without a rule.
	if ( ! isTicketsCommerce( clientId ) ) {
		return body;
	}

	BLOCK_WINDOW_KINDS.forEach( ( kind ) =>
		appendRule( body, clientId, kind, select( STORE_NAME ).getDraftRule( clientId, kind ) )
	);

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
 * Renders the sale price window options of a Tickets Commerce ticket in place of its sale dates row.
 *
 * @since TBD
 *
 * @param {Object}                       dates    The sale dates row element.
 * @param {string}                       clientId The client ID of the ticket block.
 * @param {{start: Object, end: Object}} pickers  The sale price start and end date picker elements.
 *
 * @return {Object} The sale price window options, or the row for a ticket another provider sells.
 */
export function filterSalePricePickers( dates, clientId, pickers ) {
	if ( ! isTicketsCommerce( clientId ) ) {
		return dates;
	}

	return <SalePriceWindow clientId={ clientId } pickers={ pickers } />;
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
	if ( ! isTicketsCommerce( clientId ) ) {
		return dates;
	}

	const saleWindow = resolveTicketWindow( select( STORE_NAME ).getDraftRule( clientId ), readEventDates() );

	if ( ! saleWindow ) {
		return dates;
	}

	const { date: dateFormat } = getSettings().formats;
	const format = ( date, ticketDate ) => ( date?.isValid() ? formatSaleDate( dateFormat, date ) : ticketDate );

	return { fromDate: format( saleWindow.start, dates.fromDate ), toDate: format( saleWindow.end, dates.toDate ) };
}

/**
 * Keeps a ticket from being created or updated while a window the server judges is invalid: it does not end after it
 * starts, starts outside its parent window, or a relative number is missing or out of range. The sales window is
 * judged first, and the sale price only when the save keeps it.
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

	const eventDates = readEventDates();
	const readWindow = getTicketWindowReader( clientId, select, ( kind ) => ( {
		formDates: kind.readFormDates( state, clientId ),
		isKept: kind.isKept( state, clientId ),
	} ) );

	return BLOCK_WINDOW_KINDS.some( ( kind ) => null !== getTicketWindowError( kind, readWindow, eventDates ) );
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
