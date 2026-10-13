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
import { MODE_RELATIVE } from '../rule-constants';
import { resolveSaleWindow } from '../sale-window';
import { isRelativeValueInRange } from '../window-check';
import { getLocalizedData } from './localized-data';

/** @typedef {import( 'moment' ).Moment} Moment */
/** @typedef {import( '../sale-window' ).ResolvedSaleWindow} ResolvedSaleWindow */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Works out the sales window a rule gives for the event dates.
 *
 * A relative end whose number is cleared, not whole or out of range has no date, as the save rejects it: the shared
 * resolver would count a cleared one as zero units, which is the event date itself.
 *
 * @since TBD
 *
 * @param {SaleWindowRule|null} rule       The ticket's rule, or `null` when it has none.
 * @param {EventDates|null}     eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {ResolvedSaleWindow|null} The sales window, or `null` without a rule or the event dates.
 */
export function resolveTicketWindow( rule, eventDates ) {
	if ( ! rule || ! eventDates ) {
		return null;
	}

	const saleWindow = resolveSaleWindow( rule, eventDates.start, eventDates.end, eventDates.timezone );
	const hasNoDate = ( end ) => MODE_RELATIVE === end.mode && ! isRelativeValueInRange( end.value );

	return {
		...saleWindow,
		start: hasNoDate( rule.start ) ? null : saleWindow.start,
		end: hasNoDate( rule.end ) ? null : saleWindow.end,
	};
}

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
