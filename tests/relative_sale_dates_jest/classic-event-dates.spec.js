import { readEventDates } from '@tec/tickets/relative-sale-dates/classic/event-dates';

global.DateFormatter = require( 'php-date-formatter' );

/**
 * @param {Object} overrides The settings to replace.
 *
 * @return {Object} The settings the script is localized with, for a site on the default all-day cutoff.
 */
function settings( overrides = {} ) {
	return {
		datepickerFormat: 'n/j/Y',
		timezones: { 'UTC+1': 'Europe/Paris', 'UTC+14': 'UTC' },
		allDay: { start: '00:00:00', end: '23:59:59', endDays: 0 },
		...overrides,
	};
}

/**
 * @param {Object} overrides The field values to replace.
 *
 * @return {Object} The event date fields of a three-hour evening event.
 */
function fields( overrides = {} ) {
	return {
		startDate: '6/24/2027',
		startTime: '7:00pm',
		endDate: '6/24/2027',
		endTime: '10:00pm',
		allDay: false,
		timezone: 'America/New_York',
		...overrides,
	};
}

describe( 'readEventDates', () => {
	it( 'should read 12-hour times in the datepicker format', () => {
		expect( readEventDates( fields(), settings() ) ).toStrictEqual( {
			start: '2027-06-24 19:00:00',
			end: '2027-06-24 22:00:00',
			timezone: 'America/New_York',
		} );
	} );

	it( 'should read 24-hour times', () => {
		const dates = readEventDates( fields( { startTime: '19:00', endTime: '22:30' } ), settings() );

		expect( [ dates.start, dates.end ] ).toStrictEqual( [ '2027-06-24 19:00:00', '2027-06-24 22:30:00' ] );
	} );

	it( 'should read times with a leading zero, as a 24-hour timepicker shows them', () => {
		const dates = readEventDates( fields( { startTime: '08:30', endTime: '09:05am' } ), settings() );

		expect( [ dates.start, dates.end ] ).toStrictEqual( [ '2027-06-24 08:30:00', '2027-06-24 09:05:00' ] );
	} );

	it( 'should read the dates in another datepicker format', () => {
		const dates = readEventDates(
			fields( { startDate: '2027-06-24', endDate: '2027-06-25' } ),
			settings( { datepickerFormat: 'Y-m-d' } )
		);

		expect( [ dates.start, dates.end ] ).toStrictEqual( [ '2027-06-24 19:00:00', '2027-06-25 22:00:00' ] );
	} );

	it( 'should use the all-day start and end times the server saves', () => {
		const dates = readEventDates(
			fields( { allDay: true, endDate: '6/26/2027' } ),
			settings( { allDay: { start: '06:00:00', end: '05:59:59', endDays: 1 } } )
		);

		expect( [ dates.start, dates.end ] ).toStrictEqual( [ '2027-06-24 06:00:00', '2027-06-27 05:59:59' ] );
	} );

	it( 'should use the zone the server resolves a manual offset to', () => {
		expect( readEventDates( fields( { timezone: 'UTC+1' } ), settings() ).timezone ).toBe( 'Europe/Paris' );
		expect( readEventDates( fields( { timezone: 'UTC+14' } ), settings() ).timezone ).toBe( 'UTC' );
	} );

	it( 'should return null when a date cannot be read', () => {
		expect( readEventDates( fields( { startDate: '' } ), settings() ) ).toBeNull();
		expect( readEventDates( fields( { endTime: 'soon' } ), settings() ) ).toBeNull();
	} );
} );
