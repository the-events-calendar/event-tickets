/**
 * Works out the text that tells the admin how long a sale price lasts, for the classic ticket form and the Ticket block.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
// Only UTC dates are read here, so `moment` needs no zone data of its own in either editor.
import moment from 'moment';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MODE_NOW, MODE_SPECIFIC } from './rule-constants';
import { isValidSalePriceRule, resolveSalePriceDates } from './sale-price-window';
import { DATE_FORMAT } from './sale-window';

/** @typedef {import( './sale-price-window' ).SalePriceRule} SalePriceRule */
/** @typedef {import( './server-event-dates' ).EventDates} EventDates */

/**
 * Gets the text that tells the admin how long the sale price lasts.
 *
 * Each date is a day, as the server stores the sale price dates. A relative boundary takes the day it resolves to in
 * the event timezone, a *now* start today, and a specific boundary the day its date field holds. A rule the server
 * would reject, such as one with a cleared number, has no length: `moment` subtracts a `NaN` amount as 0, so the
 * boundary would otherwise count from the event start.
 *
 * @since TBD
 *
 * @param {SalePriceRule}                  rule       The rule the sale price fields express.
 * @param {EventDates}                     eventDates The event dates, as the server reads them.
 * @param {{start: ?string, end: ?string}} formDates  The specific dates the form holds, `YYYY-MM-DD`, or `null`.
 * @param {string}                         today      Today in the event timezone, `YYYY-MM-DD`.
 *
 * @return {string} The text, or an empty string when the rule is invalid, a boundary has no date, or the sale price
 *                  does not end after the day it starts.
 */
export function getSalePriceLengthText( rule, eventDates, formDates, today ) {
	if ( ! isValidSalePriceRule( rule ) ) {
		return '';
	}

	const resolved = resolveSalePriceDates( rule, eventDates );
	const getDate = ( key ) => {
		if ( MODE_SPECIFIC === rule[ key ].mode ) {
			return formDates[ key ];
		}

		return MODE_NOW === rule[ key ].mode ? today : resolved[ key ];
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
