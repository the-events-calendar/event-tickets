/**
 * Checks a ticket block's sale price window the way the server checks it on save.
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
import { getSalePriceError } from '../sale-price-check';
import { getFormSalesWindow } from '../window-check';
import {
	isSalePriceKept,
	readTicketFormDates,
	readTicketFormSalePriceDates,
	subscribeToCommonStore,
} from './common-store-bridge';
import { getFormRule } from './rule';
import { getTicketWindowError } from './window-error';

/** @typedef {import( '../sale-price-window' ).SalePriceRule} SalePriceRule */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Returns the error of a ticket's sale price window, or `null` when it is valid or is not judged.
 *
 * Only a sale price rule is judged: the server leaves a sale price saved without one alone, keeping its dates. It is
 * judged against the sales window a save of the form would store, as `getFormSalesWindow()` works it out, and not at
 * all while that sales window is invalid: the server rejects the save for the sales window first.
 *
 * @since TBD
 *
 * @param {SalePriceRule|null|undefined}           salePriceRule      The ticket's draft sale price rule.
 * @param {SaleWindowRule|null|undefined}          salesWindowRule    The ticket's draft sales window rule.
 * @param {EventDates|null}                        eventDates         The event dates, or `null` when they cannot be read.
 * @param {{start: string|null, end: string|null}} formDates          The sale start and end the ticket form sends.
 * @param {{start: string|null, end: string|null}} salePriceFormDates The specific sale price dates the form sends.
 *
 * @return {string|null} The message key of the error, or `null`.
 */
export function getTicketSalePriceError( salePriceRule, salesWindowRule, eventDates, formDates, salePriceFormDates ) {
	if ( ! salePriceRule || ! eventDates || null !== getTicketWindowError( salesWindowRule, eventDates, formDates ) ) {
		return null;
	}

	return getSalePriceError(
		salePriceRule,
		eventDates,
		getFormSalesWindow( getFormRule( salesWindowRule ), eventDates, formDates ),
		salePriceFormDates
	);
}

/**
 * Returns the error of a ticket block's sale price window, checked again whenever the rules, the event dates, the
 * dates the ticket form sends or its prices change. A sale price the save drops is not judged.
 *
 * @since TBD
 *
 * @param {string}                        clientId        The client ID of the ticket block.
 * @param {SalePriceRule|null|undefined}  salePriceRule   The ticket's draft sale price rule.
 * @param {SaleWindowRule|null|undefined} salesWindowRule The ticket's draft sales window rule.
 * @param {EventDates|null}               eventDates      The event dates, or `null` when they cannot be read.
 *
 * @return {string|null} The message key of the error, or `null`.
 */
export function useTicketSalePriceError( clientId, salePriceRule, salesWindowRule, eventDates ) {
	// A string, so the store's snapshot compares equal while the dates stay the same.
	const snapshot = useSyncExternalStore( subscribeToCommonStore, () =>
		JSON.stringify( [
			isSalePriceKept( window.__tribe_common_store__.getState(), clientId ),
			readTicketFormDates( clientId ),
			readTicketFormSalePriceDates( clientId ),
		] )
	);
	const [ isKept, formDates, salePriceFormDates ] = JSON.parse( snapshot );

	return isKept
		? getTicketSalePriceError( salePriceRule, salesWindowRule, eventDates, formDates, salePriceFormDates )
		: null;
}
