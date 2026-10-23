/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { getSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import {
	getLegacyState,
	getTicketSalePriceFormDates,
	isAcceptedProvider,
	isAcceptedTicket,
	isSalePriceChecked,
	isTicketReadyBesidesDuration,
	isTicketsCommerce,
} from './common-store-bridge';
import { readEventDates } from './event-dates';
import { ANCHOR_END, MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC } from '../rule-constants';
import { isSalePriceOn } from '../sale-price-status';
import { resolveWindow } from '../sale-window';
import { judgeWindow } from '../window-check';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '../window-kinds';
import { getFormRule, isSpecificWindow, toRequestRule } from './rule';
import { formatSaleDate } from './sale-dates';
import SalePriceWindow from './sale-price-window';
import SalesWindow from './sales-window';
import { STORE_NAME } from './store/constants';
import { getTicketWindowReader, hasTicketWindowSaveError, readTicketWindowForm } from './window-error';
import { BLOCK_WINDOW_KINDS } from './window-kinds';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */
/** @typedef {import( '../window-check' ).WindowReader} TicketWindowReader */

/**
 * Loads the rules of a ticket fetched from the server, sold through a provider the sales window applies to.
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
	if ( ! isAcceptedTicket( ticket ) ) {
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

	if ( ! isAcceptedTicket( ticket ) ) {
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
 * Gets what the request that creates or updates a ticket carries for one window's draft rule.
 *
 * A ticket the store knows nothing of sends no rule, so the server keeps the one stored; one whose draft has no rule
 * sends the kind's `emptyValue`. A draft the editor judges the server would reject sends no rule either, nor does one
 * whose parent window is rejected: the server would reject the ticket's other changes with it. Nor does the rule of a
 * window the ticket form does not add.
 *
 * @since TBD
 *
 * @param {string}                        clientId   The client ID of the ticket block.
 * @param {BlockWindowKind}               kind       The window kind.
 * @param {SaleWindowRule|null|undefined} rule       The ticket's draft rule of the window.
 * @param {TicketWindowReader}            readWindow Reads the draft rule and form of a window of the ticket.
 * @param {EventDates|null}               eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {string|undefined} The value the request carries, or `undefined` for one that carries none.
 */
function getSentValue( clientId, kind, rule, readWindow, eventDates ) {
	const value = rule ? JSON.stringify( toRequestRule( rule, kind ) ) : kind.emptyValue;

	if (
		undefined === value ||
		! kind.isAdded( clientId ) ||
		hasTicketWindowSaveError( kind, readWindow, eventDates )
	) {
		return undefined;
	}

	return value;
}

/**
 * Adds the ticket's draft rules to the body of the request that creates or updates it, the sales window first, and
 * records each one the request carries, so the answer to it can keep it as saved.
 *
 * The rules are recorded here, as the body is built: no later step of the save sees the body.
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
	if ( ! isAcceptedProvider( clientId ) ) {
		return body;
	}

	const eventDates = readEventDates();
	const readWindow = getTicketWindowReader( clientId, select, ( kind ) => readTicketWindowForm( clientId, kind ) );

	BLOCK_WINDOW_KINDS.forEach( ( kind ) => {
		const rule = select( STORE_NAME ).getDraftRule( clientId, kind );

		if ( undefined === rule ) {
			return;
		}

		const value = getSentValue( clientId, kind, rule, readWindow, eventDates );

		if ( undefined !== value ) {
			body.append( kind.requestKey, value );
		}

		dispatch( STORE_NAME ).setSentRule( clientId, undefined === value ? undefined : rule, kind );
	} );

	return body;
}

/**
 * Renders the sales window options of a ticket in place of its Sale Duration picker, for a provider they apply to.
 *
 * @since TBD
 *
 * @param {Object} picker   The date and time range picker element.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Object} The sales window options, or the picker for a ticket another provider sells.
 */
export function filterTicketDuration( picker, clientId ) {
	if ( ! isAcceptedProvider( clientId ) ) {
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
 * @param {{fromDate: string, toDate: string}} dates    The sale dates the header shows, in the site date and time
 *                                                      formats.
 * @param {string}                             clientId The client ID of the ticket block.
 *
 * @return {{fromDate: string, toDate: string}} The sale dates to show.
 */
export function filterSaleWindowDates( dates, clientId ) {
	const rule = select( STORE_NAME ).getDraftRule( clientId );

	// The header reads its dates on every change of the legacy store, so a ticket without a rule skips the event dates.
	if ( ! rule || ! isAcceptedProvider( clientId ) ) {
		return dates;
	}

	const saleWindow = resolveWindow( rule, readEventDates(), SALES_WINDOW );

	if ( ! saleWindow ) {
		return dates;
	}

	// @wordpress/date's `datetime` format is WordPress's own, not the site's: the header joins the site formats instead.
	const { date: dateFormat, time: timeFormat } = getSettings().formats;
	const dateTimeFormat = `${ dateFormat } ${ timeFormat }`;
	const format = ( date, ticketDate ) => ( date?.isValid() ? formatSaleDate( dateTimeFormat, date ) : ticketDate );

	return { fromDate: format( saleWindow.start, dates.fromDate ), toDate: format( saleWindow.end, dates.toDate ) };
}

/**
 * Tells the ticket header whether a ticket is on sale for the event dates in the editor, when its sale price has a rule.
 *
 * A ticket without one, or whose rule or event dates cannot be read, keeps what the server said.
 *
 * @since TBD
 *
 * @param {boolean} onSale   Whether the ticket is on sale, as the server last said.
 * @param {string}  clientId The client ID of the ticket block.
 *
 * @return {boolean} Whether the ticket is on sale.
 */
export function filterTicketOnSale( onSale, clientId ) {
	const rule = select( STORE_NAME ).getDraftRule( clientId, SALE_PRICE_WINDOW );

	// The header reads this on every change of the legacy store, so a ticket without a rule skips the event dates.
	if ( ! rule || ! isTicketsCommerce( clientId ) || ! isSalePriceChecked( clientId ) ) {
		return onSale;
	}

	const formDates = getTicketSalePriceFormDates( getLegacyState(), clientId );
	const isOn = isSalePriceOn( rule, readEventDates(), {
		start: formDates.start?.slice( 0, 10 ) ?? '',
		end: formDates.end?.slice( 0, 10 ) ?? '',
	} );

	return null === isOn ? onSale : isOn;
}

/**
 * Keeps a ticket from being created or updated while a window the server judges is invalid: it does not end after it
 * starts, starts outside its parent window, or a relative number is missing or out of range. The sales window is
 * judged first, and the sale price only when the save keeps it.
 *
 * The legacy sales duration error alone does not keep the button disabled once an end is relative or the default, since
 * the dates it checks are hidden then.
 *
 * The button follows the window at once, while the window holds a new error back until the admin stops typing: a
 * click in that pause would send a window the server rejects. The error shows within the settle delay.
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
	if ( ! isAcceptedProvider( clientId ) ) {
		return isDisabled;
	}

	const rule = select( STORE_NAME ).getDraftRule( clientId );

	// The legacy duration error judges the picker's dates, which mean nothing once an end is relative or the default.
	const onlyDurationError =
		! isSpecificWindow( getFormRule( rule ) ) && isTicketReadyBesidesDuration( state, clientId );

	if ( isDisabled && ! onlyDurationError ) {
		return true;
	}

	return hasTicketWindowError( clientId, state );
}

/**
 * Keeps saving the post from updating a ticket while a window the server judges is invalid, as the ticket's Update
 * button is kept disabled: moving the event into the past makes a sales window from *Now* to *When the event starts*
 * end before it starts.
 *
 * The server judges the rule it stores, and saving the event resolves that rule against the new event dates without
 * the update, so the update is skipped rather than sent without the draft rule.
 *
 * @since TBD
 *
 * @param {boolean} updatesTicket Whether saving the post updates the ticket.
 * @param {string}  clientId      The client ID of the ticket block.
 *
 * @return {boolean} Whether saving the post updates the ticket.
 */
export function filterUpdateTicketWithPostSave( updatesTicket, clientId ) {
	if ( ! updatesTicket || ! isAcceptedProvider( clientId ) ) {
		return updatesTicket;
	}

	return ! hasTicketWindowError( clientId );
}

/**
 * Stops the legacy sync from moving a ticket's sale end to the event start when its rule sets another end, as the
 * server's `Rule::lets_end_follow_event_start()` decides.
 *
 * A relative end resolves to its own date. A specific end is the ticket's own, which the legacy sync moves only while it
 * was never edited by hand, as it does for a ticket without a rule, unless the start counts back from the event end,
 * which could fall after an end moved to the event start. An end of *When the event starts* follows the event start, as
 * the server resolves it.
 *
 * @since TBD
 *
 * @param {boolean} followsEventStart Whether the sale end follows the event start.
 * @param {string}  clientId          The client ID of the ticket block.
 *
 * @return {boolean} Whether the sale end follows the event start.
 */
export function filterSyncSaleEndWithEventStart( followsEventStart, clientId ) {
	if ( ! isAcceptedProvider( clientId ) ) {
		return followsEventStart;
	}

	const rule = select( STORE_NAME ).getDraftRule( clientId );

	if ( ! rule || MODE_DEFAULT === rule.end.mode ) {
		return followsEventStart;
	}

	// The form keeps an anchor on a start that is not relative, which the server does not store.
	const startCountsFromEventEnd = MODE_RELATIVE === rule.start.mode && ANCHOR_END === rule.start.anchor;

	return MODE_SPECIFIC === rule.end.mode && ! startCountsFromEventEnd ? followsEventStart : false;
}

/**
 * Returns whether a window of a ticket has an error the server would reject a save of the ticket for.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} [state]  The legacy ticket state, such as the one the dashboard is rendered with; the common store's
 *                          when left out.
 *
 * @return {boolean} Whether a window has an error.
 */
function hasTicketWindowError( clientId, state ) {
	const eventDates = readEventDates();
	const readWindow = getTicketWindowReader( clientId, select, ( kind ) =>
		readTicketWindowForm( clientId, kind, state )
	);

	return BLOCK_WINDOW_KINDS.some( ( kind ) => null !== judgeWindow( kind, readWindow, eventDates ) );
}
