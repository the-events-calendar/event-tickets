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
import { MODE_RELATIVE } from '../rule-constants';
import { SALES_END_BEFORE_START } from '../validation';
import { getWindowError } from '../window-check';
import { readTicketFormDates, subscribeToCommonStore } from './common-store-bridge';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Returns the error of a ticket's sales window, or `null` when it is valid or cannot be judged.
 *
 * A relative start or end whose number the admin cleared is an error too: the server rejects a rule without an integer
 * there.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null|undefined}          rule       The ticket's draft rule.
 * @param {EventDates|null}                        eventDates The event dates, or `null` when they cannot be read.
 * @param {{start: string|null, end: string|null}} formDates  The start and end dates the ticket form sends.
 *
 * @return {string|null} The message key of the error, or `null`.
 */
export function getTicketWindowError( rule, eventDates, formDates ) {
	if ( ! rule || ! eventDates ) {
		return null;
	}

	const hasEmptyNumber = [ rule.start, rule.end ].some( ( end ) => MODE_RELATIVE === end.mode && '' === end.value );

	if ( hasEmptyNumber ) {
		return SALES_END_BEFORE_START;
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
