import moment from 'moment-timezone';
import { getWindowLengthText } from '@tec/tickets/relative-sale-dates/window-length';

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

const TIMEZONE = 'America/New_York';

/**
 * @param {string} dateTime The date and time in New York, `YYYY-MM-DD HH:mm:ss`.
 *
 * @return {moment.Moment} The date.
 */
function at( dateTime ) {
	return moment.tz( dateTime, FORMAT, true, TIMEZONE );
}

describe( 'getWindowLengthText', () => {
	it( 'should count a whole number of weeks in weeks', () => {
		const window = { start: at( '2099-06-10 19:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( 'Tickets on sale for 1 week' );
	} );

	it( 'should count several weeks in the plural', () => {
		const window = { start: at( '2099-06-03 08:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( 'Tickets on sale for 2 weeks' );
	} );

	it( 'should count any other length in days', () => {
		const window = { start: at( '2099-06-14 19:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( 'Tickets on sale for 3 days' );
	} );

	it( 'should count one day in the singular', () => {
		const window = { start: at( '2099-06-16 00:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( 'Tickets on sale for 1 day' );
	} );

	// The sale price dates are whole days: an hour across midnight is a day.
	it( 'should count the days the dates fall on in their own timezone, not the hours between them', () => {
		const window = { start: at( '2099-06-16 23:00:00' ), end: at( '2099-06-17 00:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( 'Tickets on sale for 1 day' );
	} );

	it( 'should leave out a window that does not end after the day it starts', () => {
		const window = { start: at( '2099-06-17 08:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( '' );
	} );

	it( 'should leave out a window that ends before it starts', () => {
		const window = { start: at( '2099-06-17 19:00:00' ), end: at( '2099-06-10 19:00:00' ) };

		expect( getWindowLengthText( window ) ).toBe( '' );
	} );

	it.each( [
		[ 'no start', { start: null, end: at( '2099-06-17 19:00:00' ) } ],
		[ 'no end', { start: at( '2099-06-10 19:00:00' ), end: null } ],
		[ 'an invalid end', { start: at( '2099-06-10 19:00:00' ), end: moment.invalid() } ],
	] )( 'should leave out a window with %s', ( label, window ) => {
		expect( getWindowLengthText( window ) ).toBe( '' );
	} );

	it( 'should leave out a missing window', () => {
		expect( getWindowLengthText( null ) ).toBe( '' );
	} );
} );
