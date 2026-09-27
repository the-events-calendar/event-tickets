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

describe( 'resolveSaleWindow', () => {
	/*
	 * jest.setup.js pins moment's default zone to UTC, which would hide any read of the machine's timezone.
	 * Clearing it lets a run under a non-UTC TZ catch a resolver that leans on local time.
	 */
	beforeAll( () => {
		moment.tz.setDefault();
	} );

	afterAll( () => {
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
} );
