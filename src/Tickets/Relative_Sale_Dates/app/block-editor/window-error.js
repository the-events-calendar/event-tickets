/**
 * Checks a ticket block's sales window the way the server checks it on save.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RELATIVE_VALUE_OUT_OF_RANGE, getOutOfRangeBoundary, getWindowError } from '../window-check';
import { readTicketFormDates, subscribeToCommonStore } from './common-store-bridge';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Returns a rule with its relative numbers as the ticket request sends them: integers, or `NaN` for a cleared one.
 *
 * @since TBD
 *
 * @param {SaleWindowRule} rule The ticket's draft rule, whose numbers can be strings.
 *
 * @return {SaleWindowRule} The rule, with integer numbers.
 */
function withSentNumbers( rule ) {
	return {
		start: { ...rule.start, value: parseInt( rule.start?.value, 10 ) },
		end: { ...rule.end, value: parseInt( rule.end?.value, 10 ) },
	};
}

/**
 * Returns the error of a ticket's sales window, or `null` when it is valid or cannot be judged.
 *
 * A relative start or end whose number the admin cleared is an error too, as one out of range: the server rejects a rule
 * without an integer there.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null|undefined}               rule       The ticket's draft rule.
 * @param {EventDates|null}                             eventDates The event dates, or `null` when they cannot be read.
 * @param {{start: string|null, end: string|null}|null} formDates  The start and end dates the ticket form sends, or
 *                                                                 `null` without the event dates.
 *
 * @return {string|null} The message key of the error, or `null`; without the event dates, only a relative number out
 *                       of range is judged.
 */
export function getTicketWindowError( rule, eventDates, formDates ) {
	if ( ! rule ) {
		return null;
	}

	// The server rejects a relative boundary without a whole number before it reads any date.
	if ( ! eventDates ) {
		return getOutOfRangeBoundary( withSentNumbers( rule ) ) ? RELATIVE_VALUE_OUT_OF_RANGE : null;
	}

	return getWindowError( rule, eventDates, formDates );
}

/**
 * Returns the error of a ticket block's sales window, checked again whenever the rule, the event dates or the dates the
 * ticket form sends change.
 *
 * @since TBD
 *
 * @param {string}                        clientId   The client ID of the ticket block.
 * @param {SaleWindowRule|null|undefined} rule       The ticket's draft rule.
 * @param {EventDates|null}               eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {string|null} The message key of the error, or `null`.
 */
export function useTicketWindowError( clientId, rule, eventDates ) {
	// A string, so the store's snapshot compares equal while the dates stay the same.
	const snapshot = useSyncExternalStore( subscribeToCommonStore, () =>
		JSON.stringify( readTicketFormDates( clientId ) )
	);

	return getTicketWindowError( rule, eventDates, JSON.parse( snapshot ) );
}
