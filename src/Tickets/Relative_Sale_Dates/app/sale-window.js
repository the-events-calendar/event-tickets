/**
 * Resolves a sales window rule into sale dates for an event, as the server does.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment-timezone';

/**
 * Internal dependencies
 */
import { getSaleWindowError } from './validation';

const MODE_DEFAULT = 'default';
const MODE_RELATIVE = 'relative';
const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;
const ANCHOR_START = 'start';
const ANCHOR_END = 'end';

const DATE_FORMAT = 'YYYY-MM-DD';
const TIME_FORMAT = 'HH:mm:ss';
const DATE_TIME_FORMAT = `${ DATE_FORMAT } ${ TIME_FORMAT }`;

/*
 * moment.tz() does not take a fixed offset as a zone: it warns and falls back to UTC, so offsets are applied with
 * utcOffset() instead.
 */
const FIXED_OFFSET = /^[+-]\d{2}:\d{2}$/;

/**
 * @typedef {Object} SaleWindowEnd
 *
 * @property {string} mode     One of `default`, `relative` or `specific`.
 * @property {number} [value]  The number of units before the anchor, for a relative end.
 * @property {number} [unit]   The unit, in seconds: 60, 3600, 86400 or 604800, for a relative end.
 * @property {string} [anchor] `start` or `end` of the event, for a relative end.
 */

/**
 * @typedef {Object} SaleWindowRule
 *
 * @property {SaleWindowEnd} start The start of the sales window.
 * @property {SaleWindowEnd} end   The end of the sales window.
 */

/**
 * @typedef {Object} ResolvedSaleWindow
 *
 * @property {moment.Moment|null} start    The sales start in the event timezone, or `null` when sales open at once or
 *                                         keep the ticket's own date.
 * @property {moment.Moment|null} startUtc The sales start in UTC, or `null` as for `start`.
 * @property {moment.Moment|null} end      The sales end in the event timezone, or `null` when the ticket keeps its own
 *                                         date.
 * @property {moment.Moment|null} endUtc   The sales end in UTC, or `null` as for `end`.
 * @property {boolean}            valid    Whether the sales start is before the sales end; `true` when either is
 *                                         `null`, since the window cannot be judged without both.
 */

/**
 * Resolves a rule against an event's dates.
 *
 * Only the given timezone is used: the browser's own timezone is never read.
 *
 * @since TBD
 *
 * @param {SaleWindowRule} rule       The sales window rule.
 * @param {string}         eventStart The event start, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @param {string}         eventEnd   The event end, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @param {string}         timezone   The event timezone: an IANA name, or a fixed offset such as `+03:00`.
 *
 * @return {ResolvedSaleWindow} The resolved sales window.
 */
export function resolveSaleWindow( rule, eventStart, eventEnd, timezone ) {
	const anchors = {
		[ ANCHOR_START ]: fromLocal( eventStart, timezone ),
		[ ANCHOR_END ]: fromLocal( eventEnd, timezone ),
	};

	const start = resolveRelative( rule.start, anchors, timezone );
	const end =
		MODE_DEFAULT === rule.end.mode
			? anchors[ ANCHOR_START ].clone()
			: resolveRelative( rule.end, anchors, timezone );

	return {
		start,
		startUtc: toUtc( start ),
		end,
		endUtc: toUtc( end ),
		valid: null === getSaleWindowError( start, end ),
	};
}

/**
 * Resolves one end of the window, or returns `null` when the end is not relative.
 *
 * @since TBD
 *
 * @param {SaleWindowEnd}                              end      The end of the window.
 * @param {{start: moment.Moment, end: moment.Moment}} anchors  The event start and end, in the event timezone.
 * @param {string}                                     timezone The event timezone.
 *
 * @return {moment.Moment|null} The resolved date in the event timezone, or `null` when the end is not relative.
 */
function resolveRelative( end, anchors, timezone ) {
	if ( MODE_RELATIVE !== end.mode ) {
		return null;
	}

	const anchor = anchors[ end.anchor ];

	if ( UNIT_DAYS === end.unit || UNIT_WEEKS === end.unit ) {
		const days = UNIT_WEEKS === end.unit ? end.value * 7 : end.value;

		return daysBefore( anchor, days, timezone );
	}

	return inTimezone( moment.utc( anchor.valueOf() - end.value * end.unit * 1000 ), timezone );
}

/**
 * Moves a date back by calendar days in the event timezone, keeping its wall-clock time.
 *
 * The result is built fresh from the target date and the anchor's wall-clock time rather than with `subtract()`: when
 * the target wall-clock time does not exist, a subtracted moment keeps it and formats a local time that never happens,
 * while a fresh one is pushed forward by the size of the gap, as the server's date is.
 *
 * @since TBD
 *
 * @param {moment.Moment} anchor   The date to move back, in the event timezone.
 * @param {number}        days     The number of calendar days to move back.
 * @param {string}        timezone The event timezone.
 *
 * @return {moment.Moment} The moved date, in the event timezone.
 */
function daysBefore( anchor, days, timezone ) {
	// A date at midnight UTC has no clock change to trip over.
	const targetDate = moment
		.utc( anchor.format( DATE_FORMAT ), DATE_FORMAT, true )
		.subtract( days, 'days' )
		.format( DATE_FORMAT );

	return fromLocal( `${ targetDate } ${ anchor.format( TIME_FORMAT ) }`, timezone );
}

/**
 * Builds a date from a wall-clock date and time in the event timezone.
 *
 * @since TBD
 *
 * @param {string} dateTime The date and time, `YYYY-MM-DD HH:mm:ss`.
 * @param {string} timezone The event timezone.
 *
 * @return {moment.Moment} The date, in the event timezone.
 */
function fromLocal( dateTime, timezone ) {
	if ( FIXED_OFFSET.test( timezone ) ) {
		return moment.utc( dateTime, DATE_TIME_FORMAT, true ).utcOffset( timezone, true );
	}

	return moment.tz( dateTime, DATE_TIME_FORMAT, true, timezone );
}

/**
 * Converts a date to the event timezone.
 *
 * @since TBD
 *
 * @param {moment.Moment} date     The date to convert.
 * @param {string}        timezone The event timezone.
 *
 * @return {moment.Moment} The same instant, in the event timezone.
 */
function inTimezone( date, timezone ) {
	if ( FIXED_OFFSET.test( timezone ) ) {
		return date.clone().utcOffset( timezone );
	}

	return date.clone().tz( timezone );
}

/**
 * Converts a date to UTC.
 *
 * @since TBD
 *
 * @param {moment.Moment|null} date The date to convert, or `null`.
 *
 * @return {moment.Moment|null} The same instant in UTC, or `null` when there is no date.
 */
function toUtc( date ) {
	return date ? date.clone().utc() : null;
}
