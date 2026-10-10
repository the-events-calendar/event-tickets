/**
 * Checks a window of a ticket form, of any kind, the way the server checks it on save.
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
import { MIN_VALUE, MODE_RELATIVE, MODE_SPECIFIC } from './rule-constants';
import { fromEventLocal, isStoredOpenStart, resolveWindow, toZone } from './sale-window';
import { ENDS_BEFORE_START, OUTSIDE_PARENT, RELATIVE_VALUE_OUT_OF_RANGE } from './window-errors';
import { SALES_WINDOW } from './window-kinds';

export { ENDS_BEFORE_START, OUTSIDE_PARENT, RELATIVE_VALUE_OUT_OF_RANGE };

/** @typedef {import( 'moment' ).Moment} Moment */

/** @typedef {import( './window-kinds' ).WindowKind} WindowKind */

/**
 * @typedef {Object} FormWindow
 *
 * @property {Moment|null} start The start, in the event timezone, or `null` when it has no date.
 * @property {Moment|null} end   The end, in the event timezone, or `null` when it has no date.
 */

/**
 * Gets the window a save of the form would store, as far as the form knows it.
 *
 * A relative boundary, or a default end, takes the date it resolves to; a relative one without a whole number has none.
 * A specific boundary takes the date the form sends for it, read as the server reads it, or none.
 *
 * An open start takes what the server stores for it. A kind that leaves the ticket's own start, the sales window, takes
 * the date the form sends, or now when that date is later, as the server sells it from now on; sent without a date, it
 * has none, as the server takes the day the event was published, which the form does not know. A kind that stores its
 * open start itself, the sale price, opens now.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule}           rule                The rule the form expresses.
 * @param {import( './server-event-dates' ).EventDates}        eventDates          The event dates, as the server
 *                                                                                 reads them, or `null` when they
 *                                                                                 are not known.
 * @param {{start: string|null|false, end: string|null|false}} formDates           The start and end dates the form
 *                                                                                 sends, `YYYY-MM-DD HH:mm:ss` in
 *                                                                                 the event timezone, `null` for
 *                                                                                 none, or `false` for one that
 *                                                                                 cannot be read.
 * @param {WindowKind}                                         [kind=SALES_WINDOW] The kind of the window.
 *
 * @return {FormWindow|null} The window in the event timezone, or `null` without event dates.
 */
export function getFormWindow( rule, eventDates, formDates, kind = SALES_WINDOW ) {
	const resolved = resolveWindow( rule, eventDates, kind );

	if ( ! resolved ) {
		return null;
	}

	const now = toZone( moment(), eventDates.timezone );
	const getFormDate = ( key ) =>
		formDates[ key ] ? fromEventLocal( formDates[ key ], eventDates.timezone ) : null;
	const getDate = ( key ) => {
		const { mode } = rule[ key ];

		if ( MODE_SPECIFIC === mode ) {
			return getFormDate( key );
		}

		if ( 'start' !== key || kind.openStartMode !== mode ) {
			return resolved[ key ];
		}

		if ( isStoredOpenStart( rule, kind ) ) {
			return now;
		}

		const date = getFormDate( key );

		return date && date.isAfter( now ) ? now : date;
	};

	return { start: getDate( 'start' ), end: getDate( 'end' ) };
}

/**
 * Returns whether a relative boundary's number is one the server takes for a kind.
 *
 * @since TBD
 *
 * @param {*}          value The number, `NaN` for a cleared field.
 * @param {WindowKind} kind  The kind of the window.
 *
 * @return {boolean} Whether the number is a whole number in the kind's range.
 */
function isInRange( value, kind ) {
	return Number.isInteger( value ) && value >= MIN_VALUE && value <= kind.maxValue;
}

/**
 * Returns whether one boundary of a rule is valid for a kind, as the server's `Boundary` judges it.
 *
 * @since TBD
 *
 * @param {*}          boundary The boundary, not yet validated.
 * @param {string}     key      The boundary, `start` or `end`.
 * @param {WindowKind} kind     The kind of the window.
 *
 * @return {boolean} Whether the boundary is valid.
 */
function isValidBoundary( boundary, key, kind ) {
	if ( ! boundary || 'object' !== typeof boundary || ! kind.modes[ key ].includes( boundary.mode ) ) {
		return false;
	}

	if ( MODE_RELATIVE !== boundary.mode ) {
		return true;
	}

	const isValidAnchor = kind.takesAnchor ? kind.anchors.includes( boundary.anchor ) : ! ( 'anchor' in boundary );

	return isInRange( boundary.value, kind ) && kind.units.includes( boundary.unit ) && isValidAnchor;
}

/**
 * Returns whether a rule is valid for a kind, as the server's `Rule` judges it.
 *
 * @since TBD
 *
 * @param {*}          rule                The rule, not yet validated.
 * @param {WindowKind} [kind=SALES_WINDOW] The kind of the window.
 *
 * @return {boolean} Whether the rule is valid.
 */
export function isValidRule( rule, kind = SALES_WINDOW ) {
	return (
		Boolean( rule ) &&
		'object' === typeof rule &&
		[ 'start', 'end' ].every( ( key ) => isValidBoundary( rule[ key ], key, kind ) )
	);
}

/**
 * Returns the boundary whose relative number is out of the range of a kind, the start first.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule} rule                The rule the form expresses.
 * @param {WindowKind}                               [kind=SALES_WINDOW] The kind of the window.
 *
 * @return {string|null} `start` or `end`, or `null` when both numbers are in range.
 */
export function getOutOfRangeBoundary( rule, kind = SALES_WINDOW ) {
	return (
		[ 'start', 'end' ].find(
			( key ) => MODE_RELATIVE === rule[ key ].mode && ! isInRange( rule[ key ].value, kind )
		) || null
	);
}

/**
 * Returns whether one date is before another at the precision a kind is kept at.
 *
 * @since TBD
 *
 * @param {Moment}     date  The date.
 * @param {Moment}     other The date to compare it with.
 * @param {WindowKind} kind  The kind of the window.
 *
 * @return {boolean} Whether `date` is before `other`: on an earlier day for a kind kept by the day.
 */
function isBefore( date, other, kind ) {
	return date.isBefore( other, kind.precision || 'millisecond' );
}

/**
 * Returns the error of a window of a kind, or `null` when it is valid, in the order the server checks them.
 *
 * In order:
 * - a relative number out of the kind's range is named, as the server's error for such a rule names neither the
 *   field nor the range;
 * - a rule the server does not take is rejected, as the save would keep the stored one;
 * - a specific boundary sent with a date that cannot be read is rejected, and so is one sent without a date for a
 *   kind that needs one;
 * - the window must end after it starts, at the precision the kind is kept at. A start with no date of its own, such
 *   as an open start the kind stores itself, opens with the parent window, so the end is judged against the parent's
 *   start, and only with both of the parent's ends, as the server judges it;
 * - the window's own start must fall inside the parent window, judged only with both of the parent's ends.
 *
 * Each boundary takes the date `getFormWindow()` gives it. A date that is not known, such as a default start sent
 * without one, leaves what it is part of unjudged: the server judges it with the day the event was published.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule}           rule                The rule the form expresses.
 * @param {import( './server-event-dates' ).EventDates}        eventDates          The event dates, as the server
 *                                                                                 reads them.
 * @param {{start: string|null|false, end: string|null|false}} formDates           The start and end dates the form
 *                                                                                 sends, `YYYY-MM-DD HH:mm:ss` in
 *                                                                                 the event timezone, `null` for
 *                                                                                 none, or `false` for one that
 *                                                                                 cannot be read.
 * @param {WindowKind}                                         [kind=SALES_WINDOW] The kind of the window.
 * @param {FormWindow|null}                                    [parentWindow=null] The parent window the form gives,
 *                                                                                 or `null` for a kind without one.
 *
 * @return {string|null} The error key, `ENDS_BEFORE_START`, `RELATIVE_VALUE_OUT_OF_RANGE` or `OUTSIDE_PARENT`, or
 *                       `null` when the window is valid.
 */
export function getWindowError( rule, eventDates, formDates, kind = SALES_WINDOW, parentWindow = null ) {
	if ( getOutOfRangeBoundary( rule, kind ) ) {
		return RELATIVE_VALUE_OUT_OF_RANGE;
	}

	if ( ! isValidRule( rule, kind ) ) {
		return ENDS_BEFORE_START;
	}

	const hasNoUsableDate = ( key ) => false === formDates[ key ] || ( kind.specificNeedsDate && ! formDates[ key ] );

	if ( [ 'start', 'end' ].some( ( key ) => MODE_SPECIFIC === rule[ key ].mode && hasNoUsableDate( key ) ) ) {
		return ENDS_BEFORE_START;
	}

	const dates = getFormWindow( rule, eventDates, formDates, kind );

	if ( ! dates ) {
		return null;
	}

	const parentStart = parentWindow?.start;
	const parentEnd = parentWindow?.end;
	const ownStart = isStoredOpenStart( rule, kind ) ? null : dates.start;
	const start = ownStart || ( parentEnd && parentStart ) || null;

	if ( start && dates.end && ! isBefore( start, dates.end, kind ) ) {
		return ENDS_BEFORE_START;
	}

	if (
		ownStart &&
		parentStart &&
		parentEnd &&
		( isBefore( ownStart, parentStart, kind ) || isBefore( parentEnd, ownStart, kind ) )
	) {
		return OUTSIDE_PARENT;
	}

	return null;
}
