/**
 * Builds the event dates the server reads once the event is saved, from the dates and times an editor holds.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';

/**
 * @typedef {Object} EventDateSettings
 *
 * @property {Object<string,string>} timezones The zone the server resolves each manual UTC offset option to.
 * @property {Object}                allDay    The times the server saves for an all-day event: `start` and `end`,
 *                                             `HH:mm:ss`, and `endDays`, the days `end` falls after the event's last
 *                                             day.
 */

/**
 * @typedef {Object} EventDates
 *
 * @property {string} start    The event start, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @property {string} end      The event end, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @property {string} timezone The event timezone, as the server resolves it.
 */

/**
 * Builds the event start, end and timezone the server reads once the event is saved.
 *
 * An all-day event takes the times the server saves for one, and a manual UTC offset takes the zone the server
 * resolves it to.
 *
 * @since TBD
 *
 * @param {Object}            dates           The event dates as the editor holds them.
 * @param {string}            dates.startDate The start date, `YYYY-MM-DD`.
 * @param {string|null}       dates.startTime The start time, `HH:mm:ss`, or `null` when it cannot be read.
 * @param {string}            dates.endDate   The end date, `YYYY-MM-DD`.
 * @param {string|null}       dates.endTime   The end time, `HH:mm:ss`, or `null` when it cannot be read.
 * @param {boolean}           dates.allDay    Whether the event lasts all day.
 * @param {string}            dates.timezone  The event timezone, as the editor holds it.
 * @param {EventDateSettings} settings        The settings the script is localized with.
 *
 * @return {EventDates|null} The event dates, or `null` when a timed event's time cannot be read.
 */
export function toServerEventDates( { startDate, startTime, endDate, endTime, allDay, timezone }, settings ) {
	const zone = settings.timezones[ timezone ] || timezone;

	if ( allDay ) {
		const lastDay = moment.utc( endDate ).add( settings.allDay.endDays, 'days' ).format( 'YYYY-MM-DD' );

		return {
			start: `${ startDate } ${ settings.allDay.start }`,
			end: `${ lastDay } ${ settings.allDay.end }`,
			timezone: zone,
		};
	}

	if ( ! startTime || ! endTime ) {
		return null;
	}

	return { start: `${ startDate } ${ startTime }`, end: `${ endDate } ${ endTime }`, timezone: zone };
}
