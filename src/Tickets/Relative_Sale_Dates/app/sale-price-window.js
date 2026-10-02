/**
 * Resolves a sale price rule into sale price dates for an event, and checks the rule, as the server does.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import {
	ANCHOR_START,
	MIN_VALUE,
	MODE_NOW,
	MODE_RELATIVE,
	MODE_SPECIFIC,
	SALE_PRICE_MAX_VALUE,
	UNIT_DAYS,
	UNIT_WEEKS,
} from './rule-constants';
import { DATE_FORMAT, resolveSaleWindow } from './sale-window';

/**
 * @typedef {Object} SalePriceBoundary
 *
 * @property {string} mode    One of `now`, `relative` or `specific`.
 * @property {number} [value] The number of units before the event starts, for a relative boundary.
 * @property {number} [unit]  The unit, in seconds: 86400 or 604800, for a relative boundary.
 */

/**
 * @typedef {Object} SalePriceRule
 *
 * @property {SalePriceBoundary} start The start of the sale price window.
 * @property {SalePriceBoundary} end   The end of the sale price window.
 */

/**
 * @typedef {Object} SalePriceDates
 *
 * @property {string|null} start The start, `YYYY-MM-DD` in the event timezone, `''` for a *now* start, or `null` for a
 *                               specific start or one that cannot be worked out.
 * @property {string|null} end   The end, `YYYY-MM-DD` in the event timezone, or `null` as for the start.
 */

/**
 * The units a relative sale price boundary takes: the sale price dates are whole days.
 *
 * @since TBD
 *
 * @type {number[]}
 */
const UNITS = [ UNIT_DAYS, UNIT_WEEKS ];

/**
 * Returns whether one boundary of a sale price rule is valid, as the server's `Sale_Price_Boundary` judges it, and
 * `Sale_Price_Rule` for a *now* end.
 *
 * @since TBD
 *
 * @param {*}      boundary The boundary, not yet validated.
 * @param {string} key      The boundary, `start` or `end`.
 *
 * @return {boolean} Whether the boundary is valid.
 */
function isValidBoundary( boundary, key ) {
	if ( ! boundary || 'object' !== typeof boundary ) {
		return false;
	}

	if ( MODE_SPECIFIC === boundary.mode ) {
		return true;
	}

	if ( MODE_NOW === boundary.mode ) {
		return 'start' === key;
	}

	return (
		MODE_RELATIVE === boundary.mode &&
		! ( 'anchor' in boundary ) &&
		Number.isInteger( boundary.value ) &&
		boundary.value >= MIN_VALUE &&
		boundary.value <= SALE_PRICE_MAX_VALUE &&
		UNITS.includes( boundary.unit )
	);
}

/**
 * Returns whether a sale price rule is valid, as the server's `Sale_Price_Rule` judges it.
 *
 * @since TBD
 *
 * @param {*} rule The rule, not yet validated.
 *
 * @return {boolean} Whether the rule is valid.
 */
export function isValidSalePriceRule( rule ) {
	return Boolean( rule ) && isValidBoundary( rule.start, 'start' ) && isValidBoundary( rule.end, 'end' );
}

/**
 * Resolves a sale price rule against an event's dates, as the server's `Sale_Price_Window` does.
 *
 * A relative boundary is counted back from the event start and gives the day it falls on in the event timezone.
 *
 * @since TBD
 *
 * @param {SalePriceRule}                               rule       The sale price rule.
 * @param {import( './server-event-dates' ).EventDates} eventDates The event start, end and timezone.
 *
 * @return {SalePriceDates} The sale price dates.
 */
export function resolveSalePriceDates( rule, eventDates ) {
	const toEnd = ( boundary ) =>
		MODE_RELATIVE === boundary.mode ? { ...boundary, anchor: ANCHOR_START } : { mode: MODE_SPECIFIC };
	const resolved = resolveSaleWindow(
		{ start: toEnd( rule.start ), end: toEnd( rule.end ) },
		eventDates.start,
		eventDates.end,
		eventDates.timezone
	);
	const getDate = ( key ) =>
		resolved[ key ] && resolved[ key ].isValid() ? resolved[ key ].format( DATE_FORMAT ) : null;

	return {
		start: MODE_NOW === rule.start.mode ? '' : getDate( 'start' ),
		end: getDate( 'end' ),
	};
}
