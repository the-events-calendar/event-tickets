/**
 * Writes how long the sale price of the classic ticket form lasts.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment-timezone';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ANCHOR_START, MODE_NOW, MODE_RELATIVE, MODE_SPECIFIC } from '../rule-constants';
import { DATE_FORMAT, resolveSaleWindow } from '../sale-window';

/** @typedef {import( './sale-price-rule' ).SalePriceRule} SalePriceRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Turns a sale price rule into a sales window rule the shared resolver works out, on the event start.
 *
 * A boundary that is not relative becomes a specific one, which the resolver leaves without a date.
 *
 * @since TBD
 *
 * @param {SalePriceRule} rule The sale price rule.
 *
 * @return {import( '../sale-window' ).SaleWindowRule} The rule the resolver takes.
 */
function toSaleWindowRule( rule ) {
	const toEnd = ( boundary ) =>
		MODE_RELATIVE === boundary.mode ? { ...boundary, anchor: ANCHOR_START } : { mode: MODE_SPECIFIC };

	return { start: toEnd( rule.start ), end: toEnd( rule.end ) };
}

/**
 * Gets the text that tells the admin how long the sale price lasts.
 *
 * Each date is a day, as the server stores the sale price dates. A relative boundary takes the day it resolves to in
 * the event timezone, a *now* start today, and a specific boundary the day its date field holds.
 *
 * @since TBD
 *
 * @param {SalePriceRule}                  rule       The rule the sale price fields express.
 * @param {EventDates}                     eventDates The event dates, as the server reads them.
 * @param {{start: ?string, end: ?string}} formDates  The specific dates the form holds, `YYYY-MM-DD`, or `null`.
 * @param {string}                         today      Today in the event timezone, `YYYY-MM-DD`.
 *
 * @return {string} The text, or an empty string when the sale price does not end after the day it starts.
 */
export function getSalePriceLengthText( rule, eventDates, formDates, today ) {
	const resolved = resolveSaleWindow(
		toSaleWindowRule( rule ),
		eventDates.start,
		eventDates.end,
		eventDates.timezone
	);
	const getDate = ( key ) => {
		if ( resolved[ key ] ) {
			return resolved[ key ].isValid() ? resolved[ key ].format( DATE_FORMAT ) : null;
		}

		return MODE_NOW === rule[ key ].mode ? today : formDates[ key ];
	};
	const start = getDate( 'start' );
	const end = getDate( 'end' );

	if ( ! start || ! end ) {
		return '';
	}

	const days = moment.utc( end, DATE_FORMAT, true ).diff( moment.utc( start, DATE_FORMAT, true ), 'days' );

	if ( days < 1 ) {
		return '';
	}

	if ( 0 === days % 7 ) {
		const weeks = days / 7;
		/* translators: %d: The number of weeks the ticket sale price lasts. */
		const weeksText = _n( 'Tickets on sale for %d week', 'Tickets on sale for %d weeks', weeks, 'event-tickets' );

		return sprintf( weeksText, weeks );
	}

	/* translators: %d: The number of days the ticket sale price lasts. */
	const daysText = _n( 'Tickets on sale for %d day', 'Tickets on sale for %d days', days, 'event-tickets' );

	return sprintf( daysText, days );
}
