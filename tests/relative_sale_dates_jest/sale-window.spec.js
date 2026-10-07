import moment from 'moment-timezone';
import { resolveSaleWindow } from '@tec/tickets/relative-sale-dates/sale-window';
import fixtures from '../_data/relative-sale-dates/sale-window-cases.json';

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

/**
 * Asserts a resolved date pair matches its expected local and UTC values, or that both are empty.
 *
 * @param {string|null}        expectedLocal The expected date in the event timezone, or `null` for no date.
 * @param {string|null}        expectedUtc   The expected date in UTC, or `null` for no date.
 * @param {moment.Moment|null} local         The resolved date in the event timezone.
 * @param {moment.Moment|null} utc           The resolved date in UTC.
 */
function expectDatePair( expectedLocal, expectedUtc, local, utc ) {
	if ( null === expectedLocal ) {
		expect( local ).toBeNull();
		expect( utc ).toBeNull();

		return;
	}

	expect( local.format( FORMAT ) ).toBe( expectedLocal );
	expect( utc.format( FORMAT ) ).toBe( expectedUtc );
	expect( utc.utcOffset() ).toBe( 0 );
	expect( local.valueOf() ).toBe( utc.valueOf() );
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

/*
 * The resolver must never read the machine's timezone, so every fixture runs under a zone east of UTC, one west of it
 * and UTC itself.
 */
describe.each( [ 'UTC', 'Pacific/Auckland', 'America/Los_Angeles' ] )( 'resolveSaleWindow with TZ=%s', ( machineTimezone ) => {
	const originalTimezone = process.env.TZ;

	/*
	 * jest.setup.js pins moment's default zone to UTC, which would hide any read of the machine's timezone.
	 * Clearing it lets the machine timezone below reach a resolver that leans on local time.
	 */
	beforeAll( () => {
		process.env.TZ = machineTimezone;
		moment.tz.setDefault();
	} );

	afterAll( () => {
		process.env.TZ = originalTimezone;
		moment.tz.setDefault( 'UTC' );
	} );

	it.each( fixtures.cases.map( ( fixture ) => [ fixture.name, fixture ] ) )(
		'should resolve the shared fixture case: %s',
		( name, fixture ) => {
			const window = resolveSaleWindow( fixture.rule, fixture.event_start, fixture.event_end, fixture.timezone );

			const { expected } = fixture;
			expectDatePair( expected.start_local, expected.start_utc, window.start, window.startUtc );
			expectDatePair( expected.end_local, expected.end_utc, window.end, window.endUtc );
			expect( window.valid ).toBe( expected.valid );
		}
	);

	it.each(
		fixtures.cases.flatMap( ( fixture ) =>
			[ 'start', 'end' ]
				.filter( ( key ) => 'relative' === fixture.rule[ key ].mode )
				.map( ( key ) => [ `${ fixture.name }: ${ key }`, fixture, key ] )
		)
	)( 'should resolve a relative boundary before its anchor: %s', ( name, fixture, key ) => {
		const window = resolveSaleWindow( fixture.rule, fixture.event_start, fixture.event_end, fixture.timezone );
		const anchor = eventDate( 'start' === fixture.rule[ key ].anchor ? fixture.event_start : fixture.event_end, fixture.timezone );

		expect( window[ key ].valueOf() ).toBeLessThan( anchor.valueOf() );
	} );
} );
