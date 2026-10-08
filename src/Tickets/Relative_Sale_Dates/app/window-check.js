/**
 * Checks the sales window of a ticket form the way the server checks it on save.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { MAX_VALUE, MIN_VALUE, MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC } from './rule-constants';
import { fromEventLocal, resolveSaleWindow } from './sale-window';
import { getSaleWindowError, SALES_END_BEFORE_START } from './validation';

/**
 * The message key of a relative boundary whose number is out of range.
 *
 * @since TBD
 *
 * @type {string}
 */
export const RELATIVE_VALUE_OUT_OF_RANGE = 'relative_value_out_of_range';

/**
 * Returns whether a relative boundary's number is one the server takes.
 *
 * @since TBD
 *
 * @param {number} value The number, `NaN` for a cleared field.
 *
 * @return {boolean} Whether the number is a whole number in the range.
 */
function isInRange( value ) {
	return Number.isInteger( value ) && value >= MIN_VALUE && value <= MAX_VALUE;
}

/**
 * Returns the boundary whose relative number is out of range, the start first.
 *
 * @since TBD
 *
 * @param {import( '../sale-window' ).SaleWindowRule} rule The rule the form expresses.
 *
 * @return {string|null} `start` or `end`, or `null` when both numbers are in range.
 */
export function getOutOfRangeBoundary( rule ) {
	return (
		[ 'start', 'end' ].find( ( key ) => MODE_RELATIVE === rule[ key ].mode && ! isInRange( rule[ key ].value ) ) ||
		null
	);
}

/**
 * Returns the error of a sales window, or `null` when it is valid.
 *
 * A boundary the rule resolves takes the date it resolves to. One it leaves to the ticket, a specific boundary or a
 * default start, takes the date the form sends for it, read as the server reads it; a specific boundary without a date
 * is rejected. A default start the form dates later than now takes now, as the server sells it from now on, and one
 * already past keeps its date. A default start sent without a date is left unjudged: the server judges it by the day
 * the event was published, which the form does not know.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule}    rule       The rule the form expresses.
 * @param {import( './server-event-dates' ).EventDates} eventDates The event dates, as the server reads them.
 * @param {{start: string|null, end: string|null}}      formDates  The start and end dates the form sends,
 *                                                                 `YYYY-MM-DD HH:mm:ss` in the event timezone.
 *
 * @return {string|null} The message key of the error, `RELATIVE_VALUE_OUT_OF_RANGE` or a `validation.js` one, or
 *                       `null` when the window is valid.
 */
export function getWindowError( rule, eventDates, formDates ) {
	// The input's own range is not enforced, and the server's error for such a rule names neither the field nor the range.
	if ( getOutOfRangeBoundary( rule ) ) {
		return RELATIVE_VALUE_OUT_OF_RANGE;
	}

	const resolved = resolveSaleWindow( rule, eventDates.start, eventDates.end, eventDates.timezone );
	const dates = {};

	for ( const key of [ 'start', 'end' ] ) {
		if ( resolved[ key ] ) {
			dates[ key ] = resolved[ key ];
			continue;
		}

		if ( ! formDates[ key ] ) {
			if ( MODE_SPECIFIC === rule[ key ].mode ) {
				return SALES_END_BEFORE_START;
			}

			dates[ key ] = null;
			continue;
		}

		const date = fromEventLocal( formDates[ key ], eventDates.timezone );
		const now = moment();
		dates[ key ] = MODE_DEFAULT === rule[ key ].mode && date.isAfter( now ) ? now : date;
	}

	return getSaleWindowError( dates.start, dates.end );
}
