/**
 * Tells whether a ticket's sale price applies today, as the server does.
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
import { MODE_RELATIVE } from './rule-constants';
import { DATE_FORMAT, isStoredOpenStart, resolveWindow, toZone } from './sale-window';
import { SALE_PRICE_WINDOW } from './window-kinds';

/**
 * Tells whether the sale price applies today for the event dates given.
 *
 * Mirrors `Commerce\Ticket::is_on_sale()`: the days are compared in the event timezone, an empty start has started and
 * an empty end never ends. A relative boundary takes the day it works out to, and a *Now* start is empty, as the server
 * stores it, whatever date it is given: a form can still hold one picked before the admin chose *Now*. A specific
 * boundary keeps the day it is given. A relative boundary whose number the server would reject leaves it untold.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule|null}    rule       The sale price rule, or `null` for none.
 * @param {import( './server-event-dates' ).EventDates|null} eventDates The event dates, or `null` when not known.
 * @param {{start: string, end: string}}                     stored     The stored sale price dates, `YYYY-MM-DD`, empty
 *                                                                      when the ticket has none.
 * @param {moment.Moment}                                    [now]      The current instant.
 *
 * @return {boolean|null} Whether the sale price applies today, or `null` when it cannot be told.
 */
export function isSalePriceOn( rule, eventDates, stored, now = moment() ) {
	const saleWindow = resolveWindow( rule, eventDates, SALE_PRICE_WINDOW );

	if ( ! saleWindow ) {
		return null;
	}

	const dayOf = ( key ) => {
		if ( MODE_RELATIVE === rule[ key ].mode ) {
			return saleWindow[ key ]?.isValid() ? saleWindow[ key ].format( DATE_FORMAT ) : null;
		}

		return 'start' === key && isStoredOpenStart( rule, SALE_PRICE_WINDOW ) ? '' : stored[ key ];
	};
	const start = dayOf( 'start' );
	const end = dayOf( 'end' );

	if ( null === start || null === end ) {
		return null;
	}

	const today = toZone( now, eventDates.timezone ).format( DATE_FORMAT );

	// Zero-padded `YYYY-MM-DD` strings sort in date order, so the days compare without parsing them again.
	return ( ! start || start <= today ) && ( ! end || today <= end );
}
