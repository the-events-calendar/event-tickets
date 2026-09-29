/**
 * Reads the event dates from The Events Calendar's block editor, the way the server reads the saved event.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useMemo, useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { toServerEventDates } from '../server-event-dates';
import { getEventDateFields, subscribeToCommonStore } from './common-store-bridge';
import { getLocalizedData } from './localized-data';

/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */

/**
 * Reads the event start, end and timezone the server reads once the event is saved.
 *
 * @since TBD
 *
 * @param {Object|null} fields The event dates as The Events Calendar holds them; read from its store when not given.
 *
 * @return {EventDates|null} The event dates, or `null` when they cannot be read.
 */
export function readEventDates( fields = getEventDateFields() ) {
	if ( ! fields || ! fields.start || ! fields.end || ! fields.timeZone ) {
		return null;
	}

	const [ startDate, startTime ] = fields.start.split( ' ' );
	const [ endDate, endTime ] = fields.end.split( ' ' );

	return toServerEventDates(
		{ startDate, startTime, endDate, endTime, allDay: fields.allDay, timezone: fields.timeZone },
		getLocalizedData()
	);
}

/**
 * Returns the event dates, read again whenever the admin changes them in the editor.
 *
 * @since TBD
 *
 * @return {EventDates|null} The event dates, or `null` when they cannot be read.
 */
export function useEventDates() {
	// A string, so the store's snapshot compares equal while the dates stay the same.
	const snapshot = useSyncExternalStore( subscribeToCommonStore, () => JSON.stringify( getEventDateFields() ) );

	return useMemo( () => readEventDates( JSON.parse( snapshot ) ), [ snapshot ] );
}
