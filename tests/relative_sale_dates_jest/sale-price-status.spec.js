import moment from 'moment';
import { isSalePriceOn } from '@tec/tickets/relative-sale-dates/sale-price-status';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;
const EVENT = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: 'America/New_York' };
const NO_STORED = { start: '', end: '' };
const NOW_TO_ONE_WEEK = { start: { mode: 'now' }, end: { mode: 'relative', value: 1, unit: UNIT_WEEKS } };

/**
 * @param {string} isoUtc An instant, ISO 8601 in UTC.
 *
 * @return {moment.Moment} The instant.
 */
function at( isoUtc ) {
	return moment.utc( isoUtc );
}

describe( 'isSalePriceOn', () => {
	it( 'should apply from a Now start up to the day a relative end works out to, inclusive', () => {
		// One week before the June 24 event is June 17.
		expect( isSalePriceOn( NOW_TO_ONE_WEEK, EVENT, NO_STORED, at( '2099-06-17T15:00:00Z' ) ) ).toBe( true );
		expect( isSalePriceOn( NOW_TO_ONE_WEEK, EVENT, NO_STORED, at( '2099-06-18T15:00:00Z' ) ) ).toBe( false );
	} );

	it( 'should read today in the event timezone, not the browser one', () => {
		// 02:00 UTC on June 18 is 22:00 on June 17 in New York.
		expect( isSalePriceOn( NOW_TO_ONE_WEEK, EVENT, NO_STORED, at( '2099-06-18T02:00:00Z' ) ) ).toBe( true );
	} );

	it( 'should not apply before the day a relative start works out to', () => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
			end: { mode: 'relative', value: 1, unit: UNIT_DAYS },
		};

		// Two weeks before the June 24 event is June 10.
		expect( isSalePriceOn( rule, EVENT, NO_STORED, at( '2099-06-09T15:00:00Z' ) ) ).toBe( false );
		expect( isSalePriceOn( rule, EVENT, NO_STORED, at( '2099-06-10T15:00:00Z' ) ) ).toBe( true );
	} );

	it( 'should take the stored date of a specific end', () => {
		const rule = { start: { mode: 'now' }, end: { mode: 'specific' } };
		const stored = { start: '', end: '2099-06-20' };

		expect( isSalePriceOn( rule, EVENT, stored, at( '2099-06-20T15:00:00Z' ) ) ).toBe( true );
		expect( isSalePriceOn( rule, EVENT, stored, at( '2099-06-21T15:00:00Z' ) ) ).toBe( false );
	} );

	// The server stores a Now start empty; the form can still hold a date picked before the admin chose Now.
	it( 'should count a Now start as started whatever date it is given', () => {
		const stored = { start: '2099-06-20', end: '' };

		expect( isSalePriceOn( NOW_TO_ONE_WEEK, EVENT, stored, at( '2099-06-17T15:00:00Z' ) ) ).toBe( true );
	} );

	it( 'should not tell while a relative boundary does not resolve, whatever date it is given', () => {
		const rule = { start: { mode: 'now' }, end: { mode: 'relative', value: '', unit: UNIT_WEEKS } };
		const stored = { start: '', end: '2099-06-30' };

		expect( isSalePriceOn( rule, EVENT, stored, at( '2099-06-17T15:00:00Z' ) ) ).toBeNull();
	} );

	it( 'should not tell without a rule or event dates', () => {
		expect( isSalePriceOn( null, EVENT, NO_STORED ) ).toBeNull();
		expect( isSalePriceOn( NOW_TO_ONE_WEEK, null, NO_STORED ) ).toBeNull();
	} );
} );
