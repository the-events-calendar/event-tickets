/**
 * Reads the event dates from The Events Calendar's block editor, the way the server reads the saved event.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { toServerEventDates } from '../server-event-dates';
import { getEventDateFields } from './common-store-bridge';
import { getLocalizedData } from './localized-data';
import { useCommonStoreValue } from './use-common-store-value';

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
	const fields = useCommonStoreValue( getEventDateFields );

	return useMemo( () => readEventDates( fields ), [ fields ] );
}
