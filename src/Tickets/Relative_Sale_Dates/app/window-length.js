/**
 * Says how long a window lasts.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { DATE_FORMAT } from './sale-window';

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * Gets the text that says how long a window lasts, in weeks when it lasts a whole number of them and in days otherwise.
 *
 * The length counts the days the window's dates fall on, each in its own timezone, as the sale price dates are stored.
 *
 * @since TBD
 *
 * @param {{start: Moment|null, end: Moment|null}|null} window The window, or `null` when it is not known.
 *
 * @return {string} The text, or an empty string when the window does not end on a day after the one it starts on.
 */
export function getWindowLengthText( window ) {
	const getDay = ( date ) =>
		date && date.isValid() ? moment.utc( date.format( DATE_FORMAT ), DATE_FORMAT, true ) : null;
	const start = getDay( window?.start );
	const end = getDay( window?.end );

	if ( ! start || ! end ) {
		return '';
	}

	const days = end.diff( start, 'days' );

	if ( days < 1 ) {
		return '';
	}

	if ( 0 === days % 7 ) {
		const weeks = days / 7;
		/* translators: %d: The number of weeks the ticket sale price lasts. */
		const weeksText = _n( 'Tickets on sale for %d week', 'Tickets on sale for %d weeks', weeks, 'event-tickets' );

		return sprintf( weeksText, weeks );
	}

	/* translators: %d: The number of days the ticket sale price lasts. */
	const daysText = _n( 'Tickets on sale for %d day', 'Tickets on sale for %d days', days, 'event-tickets' );

	return sprintf( daysText, days );
}
