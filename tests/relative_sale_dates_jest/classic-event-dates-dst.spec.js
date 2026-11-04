/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone America/New_York
 */
import moment from 'moment-timezone';
import { readDateTime } from '@tec/tickets/relative-sale-dates/classic/event-dates';

global.DateFormatter = require( 'php-date-formatter' );

/*
 * A time is read without a date, on the day the browser runs on. In New York 2:00am to 3:00am does not exist on
 * 2027-03-14, so a time in that hour must not be read in the browser's timezone.
 */
describe( 'readDateTime on the day the browser timezone springs forward', () => {
	beforeEach( () => {
		// `jest.setup.js` makes moment default to UTC; a browser reads in its own timezone.
		moment.tz.setDefault();
		jest.useFakeTimers( { now: new Date( '2027-03-14T12:00:00Z' ) } );
	} );

	afterEach( () => {
		jest.useRealTimers();
		moment.tz.setDefault( 'UTC' );
	} );

	it.each( [ [ '2:30am' ], [ '02:30' ] ] )( 'should read %s as typed', ( time ) => {
		expect( readDateTime( '6/24/2027', time, 'n/j/Y' ) ).toBe( '2027-06-24 02:30:00' );
	} );
} );
