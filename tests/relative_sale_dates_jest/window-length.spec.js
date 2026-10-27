import moment from 'moment-timezone';
import { getFormWindowLengthText, getWindowLengthText } from '@tec/tickets/relative-sale-dates/window-length';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

const TIMEZONE = 'America/New_York';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const EVENT_DATES = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: TIMEZONE };

const NO_FORM_DATES = { start: null, end: null };

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

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( 'Tickets on sale for 1 week' );
	} );

	it( 'should count several weeks in the plural', () => {
		const window = { start: at( '2099-06-03 08:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( 'Tickets on sale for 2 weeks' );
	} );

	it( 'should count any other length in days', () => {
		const window = { start: at( '2099-06-14 19:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( 'Tickets on sale for 3 days' );
	} );

	it( 'should count one day in the singular', () => {
		const window = { start: at( '2099-06-16 00:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( 'Tickets on sale for 1 day' );
	} );

	// The sale price dates are whole days: an hour across midnight is a day.
	it( 'should count the days the dates fall on in their own timezone, not the hours between them', () => {
		const window = { start: at( '2099-06-16 23:00:00' ), end: at( '2099-06-17 00:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( 'Tickets on sale for 1 day' );
	} );

	it( 'should leave out a window that does not end after the day it starts', () => {
		const window = { start: at( '2099-06-17 08:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it( 'should leave out a window that ends before it starts', () => {
		const window = { start: at( '2099-06-17 19:00:00' ), end: at( '2099-06-10 19:00:00' ) };

		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it.each( [
		[ 'no start', { start: null, end: at( '2099-06-17 19:00:00' ) } ],
		[ 'no end', { start: at( '2099-06-10 19:00:00' ), end: null } ],
		[ 'an invalid end', { start: at( '2099-06-10 19:00:00' ), end: moment.invalid() } ],
	] )( 'should leave out a window with %s', ( label, window ) => {
		expect( getWindowLengthText( window, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it( 'should leave out a missing window', () => {
		expect( getWindowLengthText( null, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it.each( [
		[ 'weeks', '2099-06-03 08:00:00', 'weeks: 2' ],
		[ 'days', '2099-06-14 19:00:00', 'days: 3' ],
	] )( 'should word a length in %s as the kind does', ( label, start, expected ) => {
		const kind = {
			...SALE_PRICE_WINDOW,
			lengthText: { weeks: ( weeks ) => `weeks: ${ weeks }`, days: ( days ) => `days: ${ days }` },
		};

		expect( getWindowLengthText( { start: at( start ), end: at( '2099-06-17 19:00:00' ) }, kind ) ).toBe( expected );
	} );

	it( 'should leave out the length of a kind that shows none', () => {
		const window = { start: at( '2099-06-10 19:00:00' ), end: at( '2099-06-17 19:00:00' ) };

		expect( getWindowLengthText( window, SALES_WINDOW ) ).toBe( '' );
	} );
} );

describe( 'getFormWindowLengthText', () => {
	/**
	 * @param {number|string} value The number of units before the event starts, as the form holds it.
	 * @param {number}        unit  The unit, in seconds.
	 *
	 * @return {Object} A relative sale price boundary.
	 */
	const relative = ( value, unit ) => ( { mode: 'relative', value, unit } );

	// The event starts on 2099-06-24 at 19:00 in New York, so 1 week before it is 2099-06-17.
	it( 'should say how long the window the rule gives lasts', () => {
		const rule = { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getFormWindowLengthText( rule, EVENT_DATES, NO_FORM_DATES, SALE_PRICE_WINDOW ) ).toBe(
			'Tickets on sale for 1 week'
		);
	} );

	it( 'should count a specific boundary from the date the form holds for it', () => {
		const rule = { start: { mode: 'specific' }, end: relative( 1, UNIT_WEEKS ) };
		const formDates = { start: '2099-06-16 00:00:00', end: null };

		expect( getFormWindowLengthText( rule, EVENT_DATES, formDates, SALE_PRICE_WINDOW ) ).toBe(
			'Tickets on sale for 1 day'
		);
	} );

	it( 'should leave out a relative number the server would reject', () => {
		// The sale price takes 1 to 30; the classic number field allows typing past its max.
		const rule = { start: relative( 31, UNIT_DAYS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getFormWindowLengthText( rule, EVENT_DATES, NO_FORM_DATES, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it( 'should leave out a specific boundary whose date cannot be read', () => {
		const rule = { start: { mode: 'specific' }, end: relative( 1, UNIT_WEEKS ) };

		expect( getFormWindowLengthText( rule, EVENT_DATES, { start: false, end: null }, SALE_PRICE_WINDOW ) ).toBe(
			''
		);
	} );

	it.each( [
		[ 'a cleared number read as NaN', Number.NaN ],
		[ 'a cleared number kept as an empty string', '' ],
	] )( 'should leave out a start with %s rather than count it from the event start', ( label, value ) => {
		// A start on the event start, 2099-06-24, would read as 6 days to this end.
		const rule = { start: relative( value, UNIT_WEEKS ), end: { mode: 'specific' } };
		const formDates = { start: null, end: '2099-06-30 00:00:00' };

		expect( getFormWindowLengthText( rule, EVENT_DATES, formDates, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );

	it( 'should leave out a window without the event dates', () => {
		const rule = { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getFormWindowLengthText( rule, null, NO_FORM_DATES, SALE_PRICE_WINDOW ) ).toBe( '' );
	} );
} );
