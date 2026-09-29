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
 * A boundary the rule resolves takes the date it resolves to. One it leaves to the ticket, a specific boundary or a
 * default start, takes the date the form sends for it, as the server does; a specific boundary without a date is
 * rejected. A default start sent without a date is left unjudged: the server judges it by the day the event was
 * published, which the form does not know.
 *
 * @since TBD
 *
 * @param {import( '../sale-window' ).SaleWindowRule} rule       The rule the form expresses.
 * @param {import( './event-dates' ).EventDates}      eventDates The event dates, as the server reads them.
 * @param {{start: string|null, end: string|null}}    formDates  The start and end dates the form sends,
 *                                                               `YYYY-MM-DD HH:mm:ss` in the event timezone.
 *
 * @return {string|null} The message key of the error, or `null` when the window is valid.
 */
export function getWindowError( rule, eventDates, formDates ) {
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

		dates[ key ] = fromLocal( formDates[ key ], eventDates.timezone );
	}

	return getSaleWindowError( dates.start, dates.end );
}
