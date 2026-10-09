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
 * the block editor has the copy WordPress's `wp-date` loads, onto which the Ticket block script loads current zone data
 * (`block-editor/zone-data`), so both resolve dates as the server does.
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { ANCHOR_END, ANCHOR_START, MODE_DEFAULT, MODE_RELATIVE, UNIT_DAYS } from './rule-constants';
import { getSaleWindowError } from './validation';

const MINUTE_IN_MILLISECONDS = 60000;

export const DATE_FORMAT = 'YYYY-MM-DD';
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
		[ ANCHOR_START ]: fromEventLocal( eventStart, timezone ),
		[ ANCHOR_END ]: fromEventLocal( eventEnd, timezone ),
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
 * @typedef {Object} StoredDates
 *
 * @property {string|null} start The start as the server stores it: `YYYY-MM-DD` for a kind kept by the day,
 *                               `YYYY-MM-DD HH:mm:ss` for one kept as instants, both in the event timezone, the kind's
 *                               stored value for an open start, or `null` when the window gives it no date.
 * @property {string|null} end   The end, stored as the start is, or `null` when the window gives it no date.
 */

/**
 * Resolves the rule of a window of a kind against an event's dates.
 *
 * A relative boundary that names no anchor is counted from the kind's implied one. A relative boundary without a whole
 * number, as a cleared field reads, has no date: subtracting it would put the boundary on its anchor.
 *
 * @since TBD
 *
 * @param {SaleWindowRule}                              rule       The rule of the window.
 * @param {import( './server-event-dates' ).EventDates} eventDates The event dates, or `null` when they are not known.
 * @param {import( './window-kinds' ).WindowKind}       kind       The kind of the window.
 *
 * @return {ResolvedSaleWindow|null} The resolved window, or `null` without event dates.
 */
export function resolveWindow( rule, eventDates, kind ) {
	if ( ! eventDates ) {
		return null;
	}

	const withAnchor = ( boundary ) => ( { anchor: kind.anchors[ 0 ], ...boundary } );
	const resolved = resolveSaleWindow(
		{ start: withAnchor( rule.start ), end: withAnchor( rule.end ) },
		eventDates.start,
		eventDates.end,
		eventDates.timezone
	);

	[ 'start', 'end' ]
		.filter( ( key ) => MODE_RELATIVE === rule[ key ].mode && ! Number.isInteger( rule[ key ].value ) )
		.forEach( ( key ) => {
			resolved[ key ] = null;
			resolved[ `${ key }Utc` ] = null;
			resolved.valid = null;
		} );

	return resolved;
}

/**
 * Gets the dates the server stores for a resolved window of a kind.
 *
 * @since TBD
 *
 * @param {ResolvedSaleWindow|null}               resolved The resolved window, or `null` when there is none.
 * @param {import( './window-kinds' ).WindowKind} kind     The kind of the window.
 * @param {SaleWindowRule}                        rule     The rule the window was resolved from.
 *
 * @return {StoredDates|null} The stored dates, or `null` without a resolved window.
 */
export function toStoredDates( resolved, kind, rule ) {
	if ( ! resolved ) {
		return null;
	}

	const format = 'day' === kind.precision ? DATE_FORMAT : DATE_TIME_FORMAT;
	const getDate = ( key ) => {
		if ( 'start' === key && kind.openStartMode === rule.start.mode && null !== kind.openStartValue ) {
			return kind.openStartValue;
		}

		return resolved[ key ] && resolved[ key ].isValid() ? resolved[ key ].format( format ) : null;
	};

	return { start: getDate( 'start' ), end: getDate( 'end' ) };
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

	return before( anchors[ end.anchor ], end.value, end.unit, timezone );
}

/**
 * Moves a date back by an amount of wall-clock time in the event timezone, as the server does.
 *
 * The amount comes off the wall-clock time, not off the instant, so a clock change in between does not shift the
 * result: 8 hours before 08:00 is 00:00 even on a night an hour longer or shorter than usual. The result is then built
 * fresh from that wall-clock time rather than with `subtract()`: a time skipped by a clock change is pushed forward by
 * the size of the change, and a time that happens twice takes its first occurrence.
 *
 * A number of minutes or hours that lands on a time skipped by a clock change comes off the instant instead, as the
 * next valid time can fall on or after the anchor: 30 minutes before 03:15 would otherwise be 03:45.
 *
 * @since TBD
 *
 * @param {moment.Moment} anchor   The date to move back, in the event timezone.
 * @param {number}        value    The number of units to move back.
 * @param {number}        unit     The unit, in seconds: 60, 3600, 86400 or 604800.
 * @param {string}        timezone The event timezone.
 *
 * @return {moment.Moment} The moved date, in the event timezone.
 */
function before( anchor, value, unit, timezone ) {
	const seconds = value * unit;
	// UTC has no clock change, so the subtraction moves the wall-clock time and nothing else.
	const wallClock = moment
		.utc( anchor.format( DATE_TIME_FORMAT ), DATE_TIME_FORMAT, true )
		.subtract( seconds, 'seconds' )
		.format( DATE_TIME_FORMAT );
	const date = fromLocal( wallClock, timezone );

	// Days and weeks keep their wall-clock time: a skipped time a day or more back still lands well before the anchor.
	if ( unit >= UNIT_DAYS || date.format( DATE_TIME_FORMAT ) === wallClock ) {
		return date;
	}

	return anchor.clone().subtract( seconds, 'seconds' );
}

/**
 * Builds an event date from its wall-clock date and time, taking the occurrence the server takes.
 *
 * A time that happens twice is read as PHP reads it, with the offset in effect at that wall-clock time read as UTC:
 * the second occurrence east of UTC and the first west of it. The Events Calendar stores the event's UTC dates the same
 * way, so the window is judged against the event the server has.
 *
 * @since TBD
 *
 * @param {string} dateTime The date and time, `YYYY-MM-DD HH:mm:ss`.
 * @param {string} timezone The event timezone.
 *
 * @return {moment.Moment} The date, in the event timezone.
 */
export function fromEventLocal( dateTime, timezone ) {
	const date = fromLocal( dateTime, timezone );
	const zone = FIXED_OFFSET.test( timezone ) ? null : moment.tz.zone( timezone );

	if ( ! zone ) {
		return date;
	}

	const asUtc = moment.utc( dateTime, DATE_TIME_FORMAT, true ).valueOf();
	const serverDate = moment.tz( asUtc + zone.utcOffset( asUtc ) * MINUTE_IN_MILLISECONDS, timezone );

	// A time a clock change skips does not exist, so it keeps the date moment builds for it.
	return serverDate.format( DATE_TIME_FORMAT ) === dateTime ? serverDate : date;
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
 * Gets the wall-clock time of an instant in the event timezone.
 *
 * @since TBD
 *
 * @param {moment.Moment} instant  The instant, which is left as it is.
 * @param {string}        timezone The event timezone: an IANA name, or a fixed offset such as `+03:00`.
 *
 * @return {moment.Moment} The same instant, in the event timezone.
 */
export function toZone( instant, timezone ) {
	if ( FIXED_OFFSET.test( timezone ) ) {
		return instant.clone().utcOffset( timezone );
	}

	return instant.clone().tz( timezone );
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
