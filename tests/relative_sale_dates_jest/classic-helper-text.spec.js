/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone America/New_York
 */
import moment from 'moment-timezone';
import { formatHelperText } from '@tec/tickets/relative-sale-dates/classic/helper-text';

global.DateFormatter = require( 'php-date-formatter' );

const SETTINGS = {
	dateWithYear: 'F j, Y',
	dateNoYear: 'F j',
	timeFormat: 'g:i a',
	dateSettings: {},
};

/*
 * A zone far from the one the tests run in, so a date formatted in the browser's zone instead of the event's would
 * show another day.
 */
const TIMEZONE = 'Pacific/Kiritimati';

describe( 'formatHelperText', () => {
	it( 'should leave the year out of a date in the current year', () => {
		const date = moment.tz( '2027-06-10 19:00:00', TIMEZONE );

		expect( formatHelperText( 'Sales start %1$s at %2$s', date, SETTINGS, 2027 ) ).toBe(
			'Sales start June 10 at 7:00 pm'
		);
	} );

	it( 'should show the year of a date in another year', () => {
		const date = moment.tz( '2028-01-05 08:30:00', TIMEZONE );

		expect( formatHelperText( 'Sales end %1$s at %2$s', date, SETTINGS, 2027 ) ).toBe(
			'Sales end January 5, 2028 at 8:30 am'
		);
	} );

	it( 'should follow the date and time formats of the site', () => {
		const date = moment.tz( '2027-06-10 19:00:00', TIMEZONE );
		const settings = { ...SETTINGS, dateNoYear: 'd/m', timeFormat: 'H:i' };

		expect( formatHelperText( 'Sales start %1$s at %2$s', date, settings, 2027 ) ).toBe(
			'Sales start 10/06 at 19:00'
		);
	} );

	it( "should show an event time that the browser's own clocks skip", () => {
		// This file runs in New York, which skips from 02:00 to 03:00 that night; London does not.
		const date = moment.tz( '2027-03-14 02:30:00', 'Europe/London' );

		expect( formatHelperText( 'Sales start %1$s at %2$s', date, SETTINGS, 2027 ) ).toBe(
			'Sales start March 14 at 2:30 am'
		);
	} );
} );
