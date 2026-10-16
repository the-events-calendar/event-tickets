/**
 * Works out a ticket's sales window in the block editor and writes its dates the way the admin reads them.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';
import { dateI18n } from '@wordpress/date';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getHelperDateFormat } from '../helper-date-format';
import { getLocalizedData } from './localized-data';

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * Formats a sales date in its own timezone, which is the event's.
 *
 * A manual UTC offset the server resolves to a fixed offset has no zone name, so the offset stands in for it.
 *
 * @since TBD
 *
 * @param {string} format The format, in PHP date format.
 * @param {Moment} date   The date.
 *
 * @return {string} The formatted date.
 */
export function formatSaleDate( format, date ) {
	return dateI18n( format, date.format(), date.tz() || date.format( 'Z' ) );
}

/**
 * Writes the helper text that tells the admin when one end of a relative sales window works out to.
 *
 * The year is left out of a date in the current year of the event timezone, as the server formats dates.
 *
 * @since TBD
 *
 * @param {string}      name The end of the window, `start` or `end`.
 * @param {Moment|null} date The sales date, in the event timezone, or `null` when it cannot be worked out.
 *
 * @return {string} The helper text, or an empty string without a date.
 */
export function getHelperText( name, date ) {
	if ( ! date || ! date.isValid() ) {
		return '';
	}

	const { formats } = getLocalizedData();
	const dateFormat = getHelperDateFormat( date, formats, moment().utcOffset( date.utcOffset() ).year() );
	const template =
		'start' === name
			? // translators: %1$s is the date sales start on, %2$s the time.
			  __( 'Sales start %1$s at %2$s', 'event-tickets' )
			: // translators: %1$s is the date sales end on, %2$s the time.
			  __( 'Sales end %1$s at %2$s', 'event-tickets' );

	return sprintf( template, formatSaleDate( dateFormat, date ), formatSaleDate( formats.time, date ) );
}
