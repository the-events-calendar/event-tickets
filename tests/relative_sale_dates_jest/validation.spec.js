import moment from 'moment-timezone';
import { getSaleWindowError } from '@tec/tickets/relative-sale-dates/validation';

const TIMEZONE = 'America/New_York';

/**
 * Builds a date in the test timezone.
 *
 * @param {string} dateTime The date and time, `YYYY-MM-DD HH:mm:ss`.
 *
 * @return {moment.Moment} The date.
 */
function date( dateTime ) {
	return moment.tz( dateTime, 'YYYY-MM-DD HH:mm:ss', true, TIMEZONE );
}

describe( 'getSaleWindowError', () => {
	it( 'should report a start equal to the end', () => {
		expect( getSaleWindowError( date( '2027-06-10 19:00:00' ), date( '2027-06-10 19:00:00' ) ) ).toBe(
			'sales_end_before_start'
		);
	} );

	it( 'should report a start after the end', () => {
		expect( getSaleWindowError( date( '2027-06-10 19:00:01' ), date( '2027-06-10 19:00:00' ) ) ).toBe(
			'sales_end_before_start'
		);
	} );

	it( 'should compare instants, not wall-clock times', () => {
		const start = date( '2027-06-10 19:00:00' );
		// One second later, but 13:00:01 on the clock in Honolulu.
		const end = start.clone().tz( 'Pacific/Honolulu' ).add( 1, 'second' );

		expect( getSaleWindowError( start, end ) ).toBeNull();
	} );

	it( 'should accept a start before the end', () => {
		expect( getSaleWindowError( date( '2027-06-10 18:59:59' ), date( '2027-06-10 19:00:00' ) ) ).toBeNull();
	} );

	it( 'should accept a window without an end', () => {
		expect( getSaleWindowError( date( '2027-06-10 19:00:00' ), null ) ).toBeNull();
	} );

	it( 'should accept a window without a start', () => {
		expect( getSaleWindowError( null, date( '2027-06-10 19:00:00' ) ) ).toBeNull();
	} );
} );
