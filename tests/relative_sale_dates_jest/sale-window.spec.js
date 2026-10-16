/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone Pacific/Auckland
 */
import moment from 'moment-timezone';
import { DATE_FORMAT, isStoredOpenStart, resolveWindow, toZone } from '@tec/tickets/relative-sale-dates/sale-window';
import { isValidRule } from '@tec/tickets/relative-sale-dates/window-check';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
import salePriceFixtures from '../_data/relative-sale-dates/sale-price-cases.json';
import fixtures from '../_data/relative-sale-dates/sale-window-cases.json';
import { describeSharedFixtureCases } from './sale-window-cases';

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

// New Zealand Standard Time, 12 hours ahead of UTC in June, far from every fixture timezone.
describeSharedFixtureCases( -720 );

describe( 'toZone', () => {
	// 03:30 UTC on 2099-06-24 is still 2099-06-23 west of UTC.
	const INSTANT = moment.utc( '2099-06-24 03:30:00', FORMAT, true );

	it( 'should give the wall-clock time of an instant in a named timezone', () => {
		expect( toZone( INSTANT, 'America/New_York' ).format( FORMAT ) ).toBe( '2099-06-23 23:30:00' );
	} );

	it( 'should give the wall-clock time of an instant at a fixed offset', () => {
		expect( toZone( INSTANT, '-05:00' ).format( FORMAT ) ).toBe( '2099-06-23 22:30:00' );
	} );

	it( 'should leave the instant it was given as it was', () => {
		toZone( INSTANT, 'America/New_York' );

		expect( INSTANT.format( FORMAT ) ).toBe( '2099-06-24 03:30:00' );
	} );
} );

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

// 01:00 on 2099-06-24 in Auckland is still 2099-06-23 in UTC, so a day read in UTC would be a day early.
const EVENT_DATES = { start: '2099-06-24 01:00:00', end: '2099-06-24 04:00:00', timezone: 'Pacific/Auckland' };

describe( 'resolveWindow', () => {
	it( 'should count a relative boundary of a kind that takes no anchor from the event start', () => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS },
			end: { mode: 'relative', value: 1, unit: UNIT_DAYS },
		};

		const window = resolveWindow( rule, EVENT_DATES, SALE_PRICE_WINDOW );

		expect( window.start.format( FORMAT ) ).toBe( '2099-06-10 01:00:00' );
		expect( window.end.format( FORMAT ) ).toBe( '2099-06-23 01:00:00' );
	} );

	it( 'should count a relative boundary from the anchor it names', () => {
		const rule = {
			start: { mode: 'relative', value: 1, unit: UNIT_DAYS, anchor: 'end' },
			end: { mode: 'default' },
		};

		const window = resolveWindow( rule, EVENT_DATES, SALES_WINDOW );

		expect( window.start.format( FORMAT ) ).toBe( '2099-06-23 04:00:00' );
		expect( window.end.format( FORMAT ) ).toBe( '2099-06-24 01:00:00' );
	} );

	it.each( [
		[ 'the sales window', SALES_WINDOW, { mode: 'relative', value: 1, unit: UNIT_DAYS, anchor: 'start' } ],
		[ 'the sale price window', SALE_PRICE_WINDOW, { mode: 'relative', value: 1, unit: UNIT_DAYS } ],
	] )( 'should give a relative boundary of %s whose number was cleared no date', ( label, kind, end ) => {
		// Subtracting nothing would put the start on the event start.
		const window = resolveWindow( { start: { ...end, value: Number.NaN }, end }, EVENT_DATES, kind );

		expect( window.start ).toBeNull();
		expect( window.end.format( FORMAT ) ).toBe( '2099-06-23 01:00:00' );
	} );

	it.each( [
		[ 'the sales window', SALES_WINDOW, { mode: 'relative', value: 1, unit: UNIT_DAYS, anchor: 'start' } ],
		[ 'the sale price window', SALE_PRICE_WINDOW, { mode: 'relative', value: 1, unit: UNIT_DAYS } ],
	] )( 'should give a relative boundary of %s whose number is past its range no date', ( label, kind, end ) => {
		const window = resolveWindow( { start: { ...end, value: kind.maxValue + 1 }, end }, EVENT_DATES, kind );

		expect( window.start ).toBeNull();
		expect( window.end.format( FORMAT ) ).toBe( '2099-06-23 01:00:00' );
	} );

	it( 'should date the highest number the kind of window takes', () => {
		const end = { mode: 'relative', value: 1, unit: UNIT_DAYS };

		const window = resolveWindow( { start: { ...end, value: SALE_PRICE_WINDOW.maxValue }, end }, EVENT_DATES, SALE_PRICE_WINDOW );

		expect( window.start.format( FORMAT ) ).toBe(
			moment( '2099-06-24 01:00:00' ).subtract( SALE_PRICE_WINDOW.maxValue, 'days' ).format( FORMAT )
		);
	} );

	it( 'should give no window without the event dates', () => {
		const rule = { start: { mode: 'now' }, end: { mode: 'relative', value: 1, unit: UNIT_DAYS } };

		expect( resolveWindow( rule, null, SALE_PRICE_WINDOW ) ).toBeNull();
	} );

	it( 'should give no window without a rule', () => {
		expect( resolveWindow( null, EVENT_DATES, SALES_WINDOW ) ).toBeNull();
	} );
} );

describe( 'resolveWindow on the shared sale price fixtures', () => {
	it.each( salePriceFixtures.cases.map( ( fixture ) => [ fixture.name, fixture ] ) )(
		'should resolve each boundary to the day the server stores: %s',
		( name, { rule, timezone, event_start: start, event_end: end, expected } ) => {
			const window = resolveWindow( rule, { start, end, timezone }, SALE_PRICE_WINDOW );
			const day = ( key ) => ( window[ key ]?.isValid() ? window[ key ].format( DATE_FORMAT ) : null );

			// The server stores an open start as empty, a value of the kind's own rather than a resolved day.
			expect( isStoredOpenStart( rule, SALE_PRICE_WINDOW ) ).toBe( '' === expected.start );
			expect( { start: day( 'start' ), end: day( 'end' ) } ).toStrictEqual( {
				start: expected.start || null,
				end: expected.end,
			} );
		}
	);
} );

describe( 'isValidRule', () => {
	it.each( [
		...fixtures.cases.map( ( fixture ) => [ 'sales window', fixture.name, fixture.rule, SALES_WINDOW ] ),
		...salePriceFixtures.cases.map( ( fixture ) => [
			'sale price',
			fixture.name,
			fixture.rule,
			SALE_PRICE_WINDOW,
		] ),
	] )( 'should accept the shared %s fixture rule: %s', ( label, name, rule, kind ) => {
		expect( isValidRule( rule, kind ) ).toBe( true );
	} );

	it.each( [
		...fixtures.invalid_rules.map( ( fixture ) => [ 'sales window', fixture.name, fixture.rule, SALES_WINDOW ] ),
		...salePriceFixtures.invalid_rules.map( ( fixture ) => [
			'sale price',
			fixture.name,
			fixture.rule,
			SALE_PRICE_WINDOW,
		] ),
	] )( 'should reject the shared %s fixture invalid rule: %s', ( label, name, rule, kind ) => {
		expect( isValidRule( rule, kind ) ).toBe( false );
	} );
} );
