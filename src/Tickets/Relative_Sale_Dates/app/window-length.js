/**
 * Says how long a window lasts.
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
import { DATE_FORMAT } from './sale-window';
import { getFormWindow, isValidRule } from './window-check';

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * Gets the text that says how long a window lasts, in weeks when it lasts a whole number of them and in days otherwise,
 * worded by the window's kind.
 *
 * The length counts the days the window's dates fall on, each in its own timezone, as a kind kept at the precision of a
 * day stores them.
 *
 * @since TBD
 *
 * @param {{start: Moment|null, end: Moment|null}|null} window The window, or `null` when it is not known.
 * @param {import( './window-kinds' ).WindowKind}       kind   The kind of the window.
 *
 * @return {string} The text, or an empty string for a kind that shows none or a window that ends on no later day.
 */
export function getWindowLengthText( window, kind ) {
	const getDay = ( date ) =>
		date && date.isValid() ? moment.utc( date.format( DATE_FORMAT ), DATE_FORMAT, true ) : null;
	const start = getDay( window?.start );
	const end = getDay( window?.end );

	if ( ! kind.lengthText || ! start || ! end ) {
		return '';
	}

	const days = end.diff( start, 'days' );

	if ( days < 1 ) {
		return '';
	}

	return 0 === days % 7 ? kind.lengthText.weeks( days / 7 ) : kind.lengthText.days( days );
}

/**
 * Gets the text that says how long the window a form's rule gives lasts, as a save of the form would store it.
 *
 * A rule the server would reject, such as one with a cleared or out-of-range number, has no length: the save keeps
 * the stored window.
 *
 * @since TBD
 *
 * @param {import( './sale-window' ).SaleWindowRule}           rule       The rule the form expresses.
 * @param {import( './server-event-dates' ).EventDates|null}   eventDates The event dates, as the server reads them,
 *                                                                        or `null` when they are not known.
 * @param {{start: string|null|false, end: string|null|false}} formDates  The start and end dates the form sends, as
 *                                                                        `getFormWindow()` reads them.
 * @param {import( './window-kinds' ).WindowKind}              kind       The kind of the window.
 *
 * @return {string} The text, or an empty string when the window has no length to tell.
 */
export function getFormWindowLengthText( rule, eventDates, formDates, kind ) {
	if ( ! isValidRule( rule, kind ) ) {
		return '';
	}

	return getWindowLengthText( getFormWindow( rule, eventDates, formDates, kind ), kind );
}
