/**
 * Reads the event dates from the classic event fields the way the server reads the saved event.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment-timezone';

/**
 * The formats the event timepickers show a time in: 12-hour, as `7:00pm`, or 24-hour, as `08:30`.
 *
 * Strict parsing does not take a leading zero for `h` or `H`, so each has its two-digit twin.
 *
 * @since TBD
 *
 * @type {string[]}
 */
const TIME_FORMATS = [ 'h:mma', 'hh:mma', 'h:mm a', 'hh:mm a', 'H:mm', 'HH:mm', 'H:mm:ss', 'HH:mm:ss' ];

/**
 * @typedef {Object} EventFields
 *
 * @property {string}  startDate The event start date, in the datepicker format.
 * @property {string}  startTime The event start time, as the timepicker shows it.
 * @property {string}  endDate   The event end date, in the datepicker format.
 * @property {string}  endTime   The event end time, as the timepicker shows it.
 * @property {boolean} allDay    Whether the event lasts all day.
 * @property {string}  timezone  The value of the event timezone field.
 */

/**
 * @typedef {Object} EventDateSettings
 *
 * @property {string}                datepickerFormat The datepicker format, in PHP date format.
 * @property {Object<string,string>} timezones        The zone the server resolves each manual UTC offset option to.
 * @property {Object}                allDay           The times the server saves for an all-day event: `start` and
 *                                                    `end`, `HH:mm:ss`, and `endDays`, the days `end` falls after the
 *                                                    event's last day.
 */

/**
 * @typedef {Object} EventDates
 *
 * @property {string} start    The event start, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @property {string} end      The event end, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @property {string} timezone The event timezone, as the server resolves it.
 */

/**
 * Reads a date typed in the datepicker format.
 *
 * @since TBD
 *
 * @param {string} value  The date.
 * @param {string} format The datepicker format, in PHP date format.
 *
 * @return {string|null} The date, `YYYY-MM-DD`, or `null` when it cannot be read.
 */
function readDate( value, format ) {
	const formatter = new window.DateFormatter();
	const date = value ? formatter.parseDate( value, format ) : null;

	return date ? formatter.formatDate( date, 'Y-m-d' ) : null;
}

/**
 * Reads a time as the event timepickers show it.
 *
 * @since TBD
 *
 * @param {string} value The time.
 *
 * @return {string|null} The time, `HH:mm:ss`, or `null` when it cannot be read.
 */
function readTime( value ) {
	const time = moment( ( value || '' ).trim(), TIME_FORMATS, true );

	return time.isValid() ? time.format( 'HH:mm:ss' ) : null;
}

/**
 * Reads the event start, end and timezone from the classic event fields.
 *
 * An all-day event takes the times the server saves for one, and a manual UTC offset takes the zone the server
 * resolves it to, so the result matches what the server reads once the event is saved.
 *
 * @since TBD
 *
 * @param {EventFields}       fields   The values of the event fields.
 * @param {EventDateSettings} settings The settings the script is localized with.
 *
 * @return {EventDates|null} The event dates, or `null` when a date or time cannot be read.
 */
export function readEventDates( fields, settings ) {
	const startDate = readDate( fields.startDate, settings.datepickerFormat );
	const endDate = readDate( fields.endDate, settings.datepickerFormat );
	const timezone = settings.timezones[ fields.timezone ] || fields.timezone;

	if ( ! startDate || ! endDate ) {
		return null;
	}

	if ( fields.allDay ) {
		const lastDay = moment.utc( endDate ).add( settings.allDay.endDays, 'days' ).format( 'YYYY-MM-DD' );

		return {
			start: `${ startDate } ${ settings.allDay.start }`,
			end: `${ lastDay } ${ settings.allDay.end }`,
			timezone,
		};
	}

	const startTime = readTime( fields.startTime );
	const endTime = readTime( fields.endTime );

	if ( ! startTime || ! endTime ) {
		return null;
	}

	return { start: `${ startDate } ${ startTime }`, end: `${ endDate } ${ endTime }`, timezone };
}
