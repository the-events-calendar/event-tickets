import moment from 'moment-timezone';
import 'moment-timezone/moment-timezone-utils';
import { resolveSaleWindow } from '@tec/tickets/relative-sale-dates/sale-window';
import { getWindowError } from '@tec/tickets/relative-sale-dates/window-check';
import { SALES_END_BEFORE_START } from '@tec/tickets/relative-sale-dates/validation';

// Sales start 8 hours before an event at 08:30 on 30 April 2027 in Cairo, the night Egypt's clocks go forward.
const CAIRO_START = '2027-04-30 08:30:00';
const CAIRO_END = '2027-04-30 12:00:00';
const RULE = { start: { mode: 'relative', value: 8, unit: 3600, anchor: 'start' }, end: { mode: 'default' } };
// The server stores that start at 23:30 on the 29th, so it accepts a specific end at 23:45 on the 29th.
const SPECIFIC_END_RULE = { start: RULE.start, end: { mode: 'specific' } };
const CAIRO_EVENT = { start: CAIRO_START, end: CAIRO_END, timezone: 'Africa/Cairo' };
const FORM_DATES = { start: null, end: '2027-04-29 23:45:00' };

/**
 * Replaces Africa/Cairo with its rules up to 2022, as WordPress 6.9 ships them: Egypt went back to daylight saving
 * time in 2023.
 *
 * @return {void}
 */
function installCairoWithoutItsDaylightSavingTime() {
	const { name, abbrs, untils, offsets, population } = moment.tz.zone( 'Africa/Cairo' );

	moment.tz.add(
		moment.tz.pack( moment.tz.filterYears( { name, abbrs, untils, offsets, population }, 1900, 2022 ) )
	);
}

/**
 * @return {string} When the rule starts sales, in the event timezone.
 */
function resolveCairoStart() {
	return resolveSaleWindow( RULE, CAIRO_START, CAIRO_END, 'Africa/Cairo' ).start.format( 'YYYY-MM-DD HH:mm Z' );
}

describe( 'the Ticket block zone data', () => {
	let startOverOlderData;
	let errorOverOlderData;

	// The script loads its zone data once, onto the `moment.tz` WordPress has set up by then.
	beforeAll( () => {
		moment.tz.add( 'WP|+0130|-1u|0||' );
		installCairoWithoutItsDaylightSavingTime();
		startOverOlderData = resolveCairoStart();
		errorOverOlderData = getWindowError( SPECIFIC_END_RULE, CAIRO_EVENT, FORM_DATES );

		require( '@tec/tickets/relative-sale-dates/block-editor/zone-data' );
	} );

	it( 'should resolve a date around the Cairo clock change as the server does, over older WordPress zone data', () => {
		// What WordPress 6.9's data gives: 00:30 on the 30th.
		expect( startOverOlderData ).toBe( '2027-04-30 00:30 +02:00' );
		// 00:30 does not exist that night, so the start falls back to 23:30 on the 29th, as the server stores it.
		expect( resolveCairoStart() ).toBe( '2027-04-29 23:30 +02:00' );
	} );

	it( 'should accept the window the server accepts around the Cairo clock change, so Create and Update stay enabled', () => {
		// What WordPress 6.9's data gives: the start lands after the specific end.
		expect( errorOverOlderData ).toBe( SALES_END_BEFORE_START );
		expect( getWindowError( SPECIFIC_END_RULE, CAIRO_EVENT, FORM_DATES ) ).toBeNull();
	} );

	// WordPress's own date formatting reads these zones too, for dates of any year.
	it( 'should keep the clock changes of earlier years, such as New York summer time in 2020', () => {
		expect( moment.tz( '2020-07-01T16:00:00Z', 'America/New_York' ).format( 'HH:mm Z' ) ).toBe( '12:00 -04:00' );
		expect( moment.tz( '1990-07-01T16:00:00Z', 'Europe/London' ).format( 'HH:mm Z' ) ).toBe( '17:00 +01:00' );
	} );

	it( 'should keep the zones WordPress added, such as its own WP zone', () => {
		expect( moment.tz.zone( 'WP' ) ).not.toBeNull();
	} );
} );
