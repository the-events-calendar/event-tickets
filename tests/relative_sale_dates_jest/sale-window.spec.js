/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone Pacific/Auckland
 */
import moment from 'moment-timezone';
import { resolveWindow, toZone } from '@tec/tickets/relative-sale-dates/sale-window';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
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

	it( 'should give no window without the event dates', () => {
		const rule = { start: { mode: 'now' }, end: { mode: 'relative', value: 1, unit: UNIT_DAYS } };

		expect( resolveWindow( rule, null, SALE_PRICE_WINDOW ) ).toBeNull();
	} );
} );
