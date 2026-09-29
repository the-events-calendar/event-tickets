/**
 * Resolves a sales window rule into sale dates for an event, as the server does.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
/*
 * `moment` carries no zone data of its own: the classic editor script loads `moment-timezone` with its full data, and
 * the block editor has the copy WordPress's `wp-date` loads.
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { ANCHOR_END, ANCHOR_START, MODE_DEFAULT, MODE_RELATIVE } from './rule-constants';
import { getSaleWindowError } from './validation';

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
 * @property {boolean|null}       valid    Whether the sales start is before the sales end, or `null` when either is
 *                                         `null`: the window cannot be judged without both.
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
		valid: start && end ? null === getSaleWindowError( start, end ) : null,
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

	return before( anchors[ end.anchor ], end.value * end.unit, timezone );
}

/**
 * Moves a date back by an amount of wall-clock time in the event timezone, as the server does.
 *
 * The amount comes off the wall-clock time, not off the instant, so a clock change in between does not shift the
 * result: 8 hours before 08:00 is 00:00 even on a night an hour longer or shorter than usual. The result is then built
 * fresh from that wall-clock time rather than with `subtract()`: a time skipped by a clock change is pushed forward by
 * the size of the change, and a time that happens twice takes its first occurrence.
 *
 * @since TBD
 *
 * @param {moment.Moment} anchor   The date to move back, in the event timezone.
 * @param {number}        seconds  The amount of wall-clock time to move back, in seconds.
 * @param {string}        timezone The event timezone.
 *
 * @return {moment.Moment} The moved date, in the event timezone.
 */
function before( anchor, seconds, timezone ) {
	// UTC has no clock change, so the subtraction moves the wall-clock time and nothing else.
	const wallClock = moment
		.utc( anchor.format( DATE_TIME_FORMAT ), DATE_TIME_FORMAT, true )
		.subtract( seconds, 'seconds' )
		.format( DATE_TIME_FORMAT );

	return fromLocal( wallClock, timezone );
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
export function fromLocal( dateTime, timezone ) {
	if ( FIXED_OFFSET.test( timezone ) ) {
		return moment.utc( dateTime, DATE_TIME_FORMAT, true ).utcOffset( timezone, true );
	}

	return moment.tz( dateTime, DATE_TIME_FORMAT, true, timezone );
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
