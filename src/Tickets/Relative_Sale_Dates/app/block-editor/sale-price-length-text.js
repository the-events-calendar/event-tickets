/**
 * Works out how long a ticket block's sale price lasts, as the helper text under *Sale Ends* tells the admin.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';
import { useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getSalePriceLengthText } from '../sale-price-length';
import { DATE_FORMAT, toZone } from '../sale-window';
import { readTicketFormSalePriceDates, subscribeToCommonStore } from './common-store-bridge';

/** @typedef {import( '../sale-price-window' ).SalePriceRule} SalePriceRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Returns the text that tells the admin how long a ticket block's sale price lasts.
 *
 * A *Now* start counts from today in the event timezone, as the server dates it.
 *
 * @since TBD
 *
 * @param {SalePriceRule}                          rule       The sale price rule the options show.
 * @param {EventDates|null}                        eventDates The event dates, or `null` when they cannot be read.
 * @param {{start: string|null, end: string|null}} formDates  The specific sale price dates the form sends,
 *                                                            `YYYY-MM-DD`, or `null`.
 *
 * @return {string} The text, or an empty string when the length cannot be worked out.
 */
function getTicketSalePriceLengthText( rule, eventDates, formDates ) {
	if ( ! eventDates ) {
		return '';
	}

	return getSalePriceLengthText(
		rule,
		eventDates,
		formDates,
		toZone( moment(), eventDates.timezone ).format( DATE_FORMAT )
	);
}

/**
 * Returns the text that tells the admin how long a ticket block's sale price lasts, worked out again whenever the rule,
 * the event dates or the specific sale price dates the form holds change.
 *
 * @since TBD
 *
 * @param {string}          clientId   The client ID of the ticket block.
 * @param {SalePriceRule}   rule       The sale price rule the options show.
 * @param {EventDates|null} eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {string} The text, or an empty string when the length cannot be worked out.
 */
export function useSalePriceLengthText( clientId, rule, eventDates ) {
	// A string, so the store's snapshot compares equal while the dates stay the same.
	const snapshot = useSyncExternalStore( subscribeToCommonStore, () =>
		JSON.stringify( readTicketFormSalePriceDates( clientId ) )
	);

	return getTicketSalePriceLengthText( rule, eventDates, JSON.parse( snapshot ) );
}
