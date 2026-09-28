/**
 * Checks the sales window of the classic ticket form the way the server checks it on save.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { fromLocal, resolveSaleWindow } from '../sale-window';
import { getSaleWindowError, SALES_END_BEFORE_START } from '../validation';

const MODE_SPECIFIC = 'specific';

/**
 * Returns the error of a sales window, or `null` when it is valid.
 *
 * A relative end takes the date it resolves to, a default end the event start, and a specific end the date typed for
 * it; a specific end without a date is rejected, as the server rejects it.
 *
 * @since TBD
 *
 * @param {import( '../sale-window' ).SaleWindowRule} rule          The rule the form expresses.
 * @param {import( './event-dates' ).EventDates}      eventDates    The event dates, as the server reads them.
 * @param {{start: string|null, end: string|null}}    specificDates The dates typed for a specific start and end,
 *                                                                  `YYYY-MM-DD HH:mm:ss` in the event timezone.
 *
 * @return {string|null} The message key of the error, or `null` when the window is valid.
 */
export function getWindowError( rule, eventDates, specificDates ) {
	const resolved = resolveSaleWindow( rule, eventDates.start, eventDates.end, eventDates.timezone );
	const dates = {};

	for ( const key of [ 'start', 'end' ] ) {
		if ( MODE_SPECIFIC !== rule[ key ].mode ) {
			dates[ key ] = resolved[ key ];
			continue;
		}

		if ( ! specificDates[ key ] ) {
			return SALES_END_BEFORE_START;
		}

		dates[ key ] = fromLocal( specificDates[ key ], eventDates.timezone );
	}

	return getSaleWindowError( dates.start, dates.end );
}
