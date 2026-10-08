import {
	getOutOfRangeBoundary,
	getWindowError,
	RELATIVE_VALUE_OUT_OF_RANGE,
} from '@tec/tickets/relative-sale-dates/window-check';
import { SALES_END_BEFORE_START } from '@tec/tickets/relative-sale-dates/validation';

const UNIT_WEEKS = 604800;

const EVENT_DATES = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: 'America/New_York' };

// Sales open at once and close a week before the event starts, on 2099-06-17 at 19:00.
const DEFAULT_START_RULE = {
	start: { mode: 'default' },
	end: { mode: 'relative', value: 1, unit: UNIT_WEEKS, anchor: 'start' },
};

describe( 'getWindowError', () => {
	// A ticket switched to Now from a specific date keeps that date in the form; the server sells it from now on.
	it( 'should judge a default start the form dates later than now as now, as the server does', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: '2099-06-20 00:00:00', end: null } ) ).toBeNull();
	} );

	it( 'should judge a default start the form dates before now by that date, as the server does', () => {
		// Sales closed a week before the event, on 2020-06-17 at 19:00; the past start the form sends is later.
		const pastEvent = { start: '2020-06-24 19:00:00', end: '2020-06-24 22:00:00', timezone: 'America/New_York' };

		expect( getWindowError( DEFAULT_START_RULE, pastEvent, { start: '2020-06-20 00:00:00', end: null } ) ).toBe(
			SALES_END_BEFORE_START
		);
	} );

	it( 'should accept a default start the form sends before the end', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: '2099-06-01 00:00:00', end: null } ) ).toBeNull();
	} );

	// Clocks go back at 03:00 that night, so 02:00 to 03:00 happens twice; PHP reads both times in the second hour.
	it( 'should read a form date that happens twice as the server reads it, like the event date', () => {
		const rule = { start: { mode: 'specific' }, end: { mode: 'default' } };
		const event = { start: '2099-10-25 02:45:00', end: '2099-10-25 05:00:00', timezone: 'Europe/Berlin' };

		expect( getWindowError( rule, event, { start: '2099-10-25 02:50:00', end: null } ) ).toBe( SALES_END_BEFORE_START );
	} );

	it( 'should leave a default start sent without a date unjudged', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: null, end: null } ) ).toBeNull();
	} );

	it( 'should reject a specific boundary sent without its date', () => {
		const rule = { start: { mode: 'specific' }, end: { mode: 'default' } };

		expect( getWindowError( rule, EVENT_DATES, { start: null, end: null } ) ).toBe( SALES_END_BEFORE_START );
	} );

	it.each( [
		[ 'start', 0 ],
		[ 'end', 61 ],
		[ 'start', Number.NaN ],
	] )( 'should name the %s whose number %s is out of range', ( key, value ) => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'relative', value: 1, unit: UNIT_WEEKS, anchor: 'start' },
		};
		rule[ key ] = { ...rule[ key ], value };

		expect( getWindowError( rule, EVENT_DATES, { start: null, end: null } ) ).toBe( RELATIVE_VALUE_OUT_OF_RANGE );
		expect( getOutOfRangeBoundary( rule ) ).toBe( key );
	} );
} );
