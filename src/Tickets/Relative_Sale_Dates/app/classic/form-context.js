/**
 * Reads what every part of the classic ticket form script works from: the data it is localized with, the event dates
 * and the dates the form sends.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { readDateTime, readEventDates } from './event-dates';

/** @typedef {import( './window-kinds' ).ClassicWindowKind} ClassicWindowKind */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * @typedef {Object} FormContext
 *
 * @property {Object} settings The data this script is localized with.
 * @property {Object} dynamic  The date formats and names TEC localizes for its own helper text.
 */

/**
 * The event dates last read, keyed by the field values they were read from.
 *
 * Every window check, helper text and length reads the event dates, several of them on each key the admin types; the
 * fields change far less often.
 *
 * @since TBD
 *
 * @type {{key: string|null, dates: EventDates|null}}
 */
let lastEventDates = { key: null, dates: null };

/**
 * Gets the data this script and TEC's helper text are localized with.
 *
 * @since TBD
 *
 * @return {FormContext|null} The data, or `null` on a page without either.
 */
export function getContext() {
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;

	return settings && dynamic ? { settings, dynamic } : null;
}

/**
 * Reads the event dates from the TEC event fields.
 *
 * The fields hold the dates as the admin last set them, saved or not, so the window is judged against the event the
 * form shows. The server judges a ticket save against the event as saved: a ticket saved before the event's new dates
 * can be accepted here and rejected there, which shows the server's message.
 *
 * @since TBD
 *
 * @param {FormContext} context The data this script is localized with.
 *
 * @return {EventDates|null} The event dates, or `null` when they cannot be read.
 */
export function getEventDates( { settings, dynamic } ) {
	const field = ( id ) => document.getElementById( id );
	const fields = {
		startDate: field( 'EventStartDate' )?.value,
		startTime: field( 'EventStartTime' )?.value,
		endDate: field( 'EventEndDate' )?.value,
		endTime: field( 'EventEndTime' )?.value,
		allDay: Boolean( field( 'allDayCheckbox' )?.checked ),
		timezone: field( 'event-timezone' )?.value,
	};
	const key = JSON.stringify( [ fields, dynamic.datepicker_format ] );

	if ( key !== lastEventDates.key ) {
		lastEventDates = {
			key,
			dates: readEventDates( fields, {
				datepickerFormat: dynamic.datepicker_format,
				timezones: settings.timezones,
				allDay: settings.allDay,
			} ),
		};
	}

	return lastEventDates.dates;
}

/**
 * Reads the dates the form sends for the specific boundaries of a window of a kind.
 *
 * A disabled field, like the date a Now start hides, is not sent, so the server does not judge it either. A date sent
 * that cannot be read is told apart from one not sent: the server rejects it rather than leave the boundary without a
 * date.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind    The kind of the window.
 * @param {FormContext}       context The data this script is localized with.
 *
 * @return {{start: string|null|false, end: string|null|false}} Each date, `YYYY-MM-DD HH:mm:ss`, `null` when it is not
 *                                                               sent, or `false` when it cannot be read.
 */
export function readFormDates( kind, { dynamic } ) {
	const field = ( id ) => {
		const input = id ? document.getElementById( id ) : null;

		return input && ! input.disabled ? input.value : undefined;
	};
	const read = ( key ) => {
		const date = field( kind.dateFields[ key ].date );

		if ( '' === ( date || '' ).trim() ) {
			return null;
		}

		return readDateTime( date, field( kind.dateFields[ key ].time ), dynamic.datepicker_format ) ?? false;
	};

	return { start: read( 'start' ), end: read( 'end' ) };
}
