/**
 * Checks the sale price window of a ticket form the way the server checks it on save.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { isValidSalePriceRule, resolveSalePriceDates } from './sale-price-window';
import { DATE_FORMAT } from './sale-window';

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * The sale price does not end after the day it starts, or its rule is not valid.
 *
 * @since TBD
 *
 * @type {string}
 */
export const SALE_PRICE_ENDS_BEFORE_START = 'sale_price_ends_before_start';

/**
 * The sale price starts outside the ticket sales window.
 *
 * @since TBD
 *
 * @type {string}
 */
export const SALE_PRICE_OUTSIDE_SALES_WINDOW = 'sale_price_outside_sales_window';

/**
 * Returns the error of a sale price window, or `null` when it is valid, in the order `Sale_Price_Save` checks them.
 *
 * Dates are compared as days, as the on-sale check reads them. A *now* start, or a specific one without a date, is
 * judged with the day sales open and never falls outside the sales window. Without both ends of the sales window
 * there is nothing to judge the sale price against, so only the rule itself is checked.
 *
 * @since TBD
 *
 * @param {import( './sale-price-window' ).SalePriceRule} rule        The rule the form expresses.
 * @param {import( './server-event-dates' ).EventDates}   eventDates  The event dates, as the server reads them.
 * @param {{start: ?Moment, end: ?Moment}}                salesWindow The ticket sales window, in the event timezone.
 * @param {{start: ?string, end: ?string}}                formDates   The specific sale price dates the form sends,
 *                                                                    `YYYY-MM-DD`, or `null`.
 *
 * @return {string|null} The message key of the error, or `null` when the window is valid.
 */
export function getSalePriceError( rule, eventDates, salesWindow, formDates ) {
	if ( ! isValidSalePriceRule( rule ) ) {
		return SALE_PRICE_ENDS_BEFORE_START;
	}

	if ( ! salesWindow.start?.isValid() || ! salesWindow.end?.isValid() ) {
		return null;
	}

	const dates = resolveSalePriceDates( rule, eventDates );
	const start = dates.start ?? formDates.start ?? '';
	const end = dates.end ?? formDates.end ?? '';
	const salesStart = salesWindow.start.format( DATE_FORMAT );

	if ( '' !== end && end <= ( '' === start ? salesStart : start ) ) {
		return SALE_PRICE_ENDS_BEFORE_START;
	}

	if ( '' !== start && ( start < salesStart || start > salesWindow.end.format( DATE_FORMAT ) ) ) {
		return SALE_PRICE_OUTSIDE_SALES_WINDOW;
	}

	return null;
}
