/* eslint-env jest */
/**
 * The shared fixture cases of the browser resolver, run by one spec file per browser timezone.
 *
 * A spec file runs in one process timezone, so each timezone the resolver must not depend on gets a file that calls
 * these.
 */
import moment from 'moment-timezone';
import { resolveSaleWindow } from '@tec/tickets/relative-sale-dates/sale-window';
import fixtures from '../_data/relative-sale-dates/sale-window-cases.json';

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

/**
 * Asserts a resolved date matches its expected local and UTC values, or that it is empty.
 *
 * @param {string|null}        expectedLocal The expected date in the event timezone, or `null` for no date.
 * @param {string|null}        expectedUtc   The expected date in UTC, or `null` for no date.
 * @param {moment.Moment|null} local         The resolved date in the event timezone.
 */
function expectDate( expectedLocal, expectedUtc, local ) {
	if ( null === expectedLocal ) {
		expect( local ).toBeNull();

		return;
	}

	expect( local.format( FORMAT ) ).toBe( expectedLocal );
	expect( local.clone().utc().format( FORMAT ) ).toBe( expectedUtc );
}

/**
 * Builds an event date the way the shared fixtures name it: local time in an IANA zone, or in a fixed offset.
 *
 * @param {string} dateTime The date and time, `YYYY-MM-DD HH:mm:ss`.
 * @param {string} timezone The IANA zone or fixed offset.
 *
 * @return {moment.Moment} The date.
 */
function eventDate( dateTime, timezone ) {
	return /^[+-]\d{2}:\d{2}$/.test( timezone )
		? moment.utc( dateTime, FORMAT, true ).utcOffset( timezone, true )
		: moment.tz( dateTime, FORMAT, true, timezone );
}

/**
 * Describes the shared fixture cases against the browser resolver in the timezone the calling file runs in.
 *
 * @param {number} expectedJuneOffset The `getTimezoneOffset()` the calling file's timezone has in June.
 */
export function describeSharedFixtureCases( expectedJuneOffset ) {
	describe( 'resolveSaleWindow', () => {
		/*
		 * jest.setup.js pins moment's default zone to UTC, which would hide any read of the machine's timezone.
		 * Clearing it lets the machine timezone the file runs in reach a resolver that leans on local time.
		 */
		beforeAll( () => {
			moment.tz.setDefault();
		} );

		afterAll( () => {
			moment.tz.setDefault( 'UTC' );
		} );

		it( 'should run in the timezone the file names', () => {
			expect( new Date( 2027, 5, 1 ).getTimezoneOffset() ).toBe( expectedJuneOffset );
		} );

		it.each( fixtures.cases.map( ( fixture ) => [ fixture.name, fixture ] ) )(
			'should resolve the shared fixture case: %s',
			( name, fixture ) => {
				const window = resolveSaleWindow(
					fixture.rule,
					fixture.event_start,
					fixture.event_end,
					fixture.timezone
				);

				const { expected } = fixture;
				expectDate( expected.start_local, expected.start_utc, window.start );
				expectDate( expected.end_local, expected.end_utc, window.end );
				expect( window.start && window.end ? window.start.isBefore( window.end ) : null ).toBe(
					expected.valid
				);
			}
		);

		/*
		 * A ticket stores the wall-clock times, and the server reads them back in the event timezone: the window it
		 * reads only keeps the resolved order when the two orders agree, in the hour a clock change repeats too.
		 */
		it.each(
			fixtures.cases
				.filter( ( fixture ) => fixture.expected.start_local && fixture.expected.end_local )
				.map( ( fixture ) => [ fixture.name, fixture ] )
		)( 'should order the resolved dates as their wall-clock times: %s', ( name, fixture ) => {
			const window = resolveSaleWindow( fixture.rule, fixture.event_start, fixture.event_end, fixture.timezone );

			expect( window.start.isBefore( window.end ) ).toBe(
				window.start.format( FORMAT ) < window.end.format( FORMAT )
			);
		} );

		it.each(
			fixtures.cases.flatMap( ( fixture ) =>
				[ 'start', 'end' ]
					.filter( ( key ) => 'relative' === fixture.rule[ key ].mode )
					.map( ( key ) => [ `${ fixture.name }: ${ key }`, fixture, key ] )
			)
		)( 'should resolve a relative boundary before its anchor: %s', ( name, fixture, key ) => {
			const window = resolveSaleWindow( fixture.rule, fixture.event_start, fixture.event_end, fixture.timezone );
			const anchor = eventDate(
				'start' === fixture.rule[ key ].anchor ? fixture.event_start : fixture.event_end,
				fixture.timezone
			);

			expect( window[ key ].valueOf() ).toBeLessThan( anchor.valueOf() );
		} );
	} );
}
