import moment from 'moment-timezone';
import {
	ENDS_BEFORE_START,
	getFormWindow,
	getOutOfRangeBoundary,
	getWindowError,
	OUTSIDE_PARENT,
	RELATIVE_VALUE_OUT_OF_RANGE,
} from '@tec/tickets/relative-sale-dates/window-check';
import { SALES_END_BEFORE_START } from '@tec/tickets/relative-sale-dates/validation';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const FORMAT = 'YYYY-MM-DD HH:mm:ss';

const NO_FORM_DATES = { start: null, end: null };

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

	it( 'should give the error a sales window that ends before it starts has always had', () => {
		expect( ENDS_BEFORE_START ).toBe( SALES_END_BEFORE_START );
	} );

	/**
	 * @param {string|null} start The start, `YYYY-MM-DD HH:mm:ss` in the event timezone, or `null` for none.
	 * @param {string|null} end   The end, `YYYY-MM-DD HH:mm:ss` in the event timezone, or `null` for none.
	 *
	 * @return {{start: ?moment.Moment, end: ?moment.Moment}} A parent window, in the event timezone.
	 */
	const parentWindow = ( start, end ) => ( {
		start: start ? moment.tz( start, EVENT_DATES.timezone ) : null,
		end: end ? moment.tz( end, EVENT_DATES.timezone ) : null,
	} );

	// Sales open on 2099-06-08 and close when the event starts.
	const SALES = parentWindow( '2099-06-08 09:00:00', '2099-06-24 19:00:00' );

	/**
	 * @param {Object} kind  The window kind.
	 * @param {number} value The number of units before the event starts.
	 * @param {number} unit  The unit, in seconds.
	 *
	 * @return {Object} A relative boundary counted from the event start, with an anchor for a kind that takes one.
	 */
	const relative = ( kind, value, unit ) =>
		kind.takesAnchor ? { mode: 'relative', value, unit, anchor: 'start' } : { mode: 'relative', value, unit };

	describe.each( [
		[ 'the sales window', SALES_WINDOW, null ],
		[ 'the sale price window', SALE_PRICE_WINDOW, SALES ],
	] )( 'for %s', ( label, kind, parent ) => {
		it( 'should reject a window that ends before it starts', () => {
			const rule = { start: relative( kind, 1, UNIT_WEEKS ), end: relative( kind, 2, UNIT_WEEKS ) };

			expect( getWindowError( rule, EVENT_DATES, NO_FORM_DATES, kind, parent ) ).toBe( ENDS_BEFORE_START );
		} );

		// 1 week before the event is 2099-06-17 at 19:00, the instant the start falls on.
		it( 'should reject a window that ends when it starts', () => {
			const rule = { start: { mode: 'specific' }, end: relative( kind, 1, UNIT_WEEKS ) };
			const formDates = { start: '2099-06-17 19:00:00', end: null };

			expect( getWindowError( rule, EVENT_DATES, formDates, kind, parent ) ).toBe( ENDS_BEFORE_START );
		} );

		// A month is not a unit either kind takes, so the server keeps the stored rule and rejects the save.
		it( 'should reject a rule the server rejects, as the save does', () => {
			const rule = { start: relative( kind, 1, 30 * UNIT_DAYS ), end: relative( kind, 1, UNIT_DAYS ) };

			expect( getWindowError( rule, EVENT_DATES, NO_FORM_DATES, kind, parent ) ).toBe( ENDS_BEFORE_START );
		} );

		it( 'should reject a specific boundary sent with a date that cannot be read, as the server does', () => {
			const rule = { start: { mode: 'specific' }, end: relative( kind, 1, UNIT_WEEKS ) };

			expect( getWindowError( rule, EVENT_DATES, { start: false, end: null }, kind, parent ) ).toBe(
				ENDS_BEFORE_START
			);
		} );

		it( 'should leave a date that cannot be read alone when its boundary is not specific', () => {
			const rule = { start: relative( kind, 2, UNIT_WEEKS ), end: relative( kind, 1, UNIT_WEEKS ) };

			expect( getWindowError( rule, EVENT_DATES, { start: false, end: false }, kind, parent ) ).toBeNull();
		} );

		// A default sales start sends no date, which the server reads as the day the event was published.
		it( 'should reject a window that ends before it starts without the parent start', () => {
			const rule = { start: relative( kind, 1, UNIT_WEEKS ), end: relative( kind, 2, UNIT_WEEKS ) };
			const withoutStart = parentWindow( null, '2099-06-24 19:00:00' );

			expect( getWindowError( rule, EVENT_DATES, NO_FORM_DATES, kind, withoutStart ) ).toBe( ENDS_BEFORE_START );
		} );

		it( 'should name the boundary whose number is above the highest the kind takes', () => {
			const rule = {
				start: relative( kind, 2, UNIT_WEEKS ),
				end: relative( kind, kind.maxValue + 1, UNIT_DAYS ),
			};

			expect( getWindowError( rule, EVENT_DATES, NO_FORM_DATES, kind, parent ) ).toBe(
				RELATIVE_VALUE_OUT_OF_RANGE
			);
			expect( getOutOfRangeBoundary( rule, kind ) ).toBe( 'end' );
		} );

		it( 'should take the highest number the kind takes', () => {
			const rule = { start: relative( kind, kind.maxValue, UNIT_DAYS ), end: relative( kind, 1, UNIT_DAYS ) };

			expect( getOutOfRangeBoundary( rule, kind ) ).toBeNull();
		} );
	} );

	describe( 'for the sale price window', () => {
		// The form sends a sale price date as midnight; the end, 1 week before the event, is 2099-06-17 at 19:00.
		it( 'should reject a window that ends later on the day it starts, as it is kept by the day', () => {
			const formDates = { start: '2099-06-17 00:00:00', end: null };
			const salePriceRule = { start: { mode: 'specific' }, end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ) };
			const salesRule = { start: { mode: 'specific' }, end: relative( SALES_WINDOW, 1, UNIT_WEEKS ) };

			expect( getWindowError( salePriceRule, EVENT_DATES, formDates, SALE_PRICE_WINDOW, SALES ) ).toBe(
				ENDS_BEFORE_START
			);
			expect( getWindowError( salesRule, EVENT_DATES, formDates, SALES_WINDOW ) ).toBeNull();
		} );

		// The server's error for an out-of-range number names neither the field nor the range.
		it( 'should name a number the sales window takes but the sale price does not', () => {
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 31, UNIT_DAYS ) };

			expect( getWindowError( rule, EVENT_DATES, NO_FORM_DATES, SALE_PRICE_WINDOW, SALES ) ).toBe(
				RELATIVE_VALUE_OUT_OF_RANGE
			);
		} );
	} );

	describe( 'with a parent window', () => {
		afterEach( () => {
			jest.useRealTimers();
		} );

		/**
		 * @param {Object}      rule      The sale price rule.
		 * @param {Object}      formDates The sale price dates the form sends.
		 * @param {Object|null} parent    The sales window.
		 *
		 * @return {string|null} The error of the sale price window.
		 */
		const judgeSalePrice = ( rule, formDates = NO_FORM_DATES, parent = SALES ) =>
			getWindowError( rule, EVENT_DATES, formDates, SALE_PRICE_WINDOW, parent );

		it( 'should accept a window inside the sales window that ends after the day it starts', () => {
			const rule = {
				start: relative( SALE_PRICE_WINDOW, 2, UNIT_WEEKS ),
				end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ),
			};

			expect( judgeSalePrice( rule ) ).toBeNull();
		} );

		// 3 weeks before the event is 2099-06-03, before sales open on 2099-06-08.
		it( 'should judge the end of a now start against the day sales open', () => {
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 3, UNIT_WEEKS ) };

			expect( judgeSalePrice( rule ) ).toBe( ENDS_BEFORE_START );
		} );

		// Today is 2099-06-01, so the end on 2099-06-10 is after now but before sales open on 2099-06-12.
		it( 'should judge a now start from the day sales open, not from now', () => {
			jest.useFakeTimers( { now: new Date( '2099-06-01T12:00:00Z' ) } );
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 2, UNIT_WEEKS ) };
			const lateSales = parentWindow( '2099-06-12 09:00:00', '2099-06-24 19:00:00' );

			expect( judgeSalePrice( rule, NO_FORM_DATES, lateSales ) ).toBe( ENDS_BEFORE_START );
		} );

		it( 'should judge the end of a specific start without a date against the day sales open', () => {
			const rule = { start: { mode: 'specific' }, end: relative( SALE_PRICE_WINDOW, 3, UNIT_WEEKS ) };

			expect( judgeSalePrice( rule ) ).toBe( ENDS_BEFORE_START );
		} );

		// 20 days before the event is 2099-06-04.
		it( 'should reject a start before sales open', () => {
			const rule = {
				start: relative( SALE_PRICE_WINDOW, 20, UNIT_DAYS ),
				end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ),
			};

			expect( judgeSalePrice( rule ) ).toBe( OUTSIDE_PARENT );
		} );

		it( 'should reject a start after sales close', () => {
			const rule = { start: { mode: 'specific' }, end: { mode: 'specific' } };

			expect( judgeSalePrice( rule, { start: '2099-06-25 00:00:00', end: '2099-06-26 00:00:00' } ) ).toBe(
				OUTSIDE_PARENT
			);
		} );

		it( 'should accept a start on the day sales open, earlier than they open', () => {
			const rule = { start: { mode: 'specific' }, end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ) };

			expect( judgeSalePrice( rule, { start: '2099-06-08 00:00:00', end: null } ) ).toBeNull();
		} );

		it( 'should never judge a now start outside the sales window', () => {
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ) };
			const lateSales = parentWindow( '2099-06-15 09:00:00', '2099-06-24 19:00:00' );

			expect( judgeSalePrice( rule, NO_FORM_DATES, lateSales ) ).toBeNull();
		} );

		// 4 weeks before the event is outside the sales window, which cannot be told without its start.
		it( 'should leave the start unjudged against the sales window without both of its ends', () => {
			const rule = {
				start: relative( SALE_PRICE_WINDOW, 4, UNIT_WEEKS ),
				end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ),
			};

			expect( judgeSalePrice( rule, NO_FORM_DATES, parentWindow( null, '2099-06-24 19:00:00' ) ) ).toBeNull();
		} );

		it( 'should leave a now start unjudged without the sales start', () => {
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 3, UNIT_WEEKS ) };

			expect( judgeSalePrice( rule, NO_FORM_DATES, null ) ).toBeNull();
		} );
	} );
} );

describe( 'getFormWindow', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	/**
	 * @param {Object} kind  The window kind.
	 * @param {number} value The number of units before the event starts.
	 * @param {number} unit  The unit, in seconds.
	 *
	 * @return {Object} A relative boundary counted from the event start, with an anchor for a kind that takes one.
	 */
	const relative = ( kind, value, unit ) =>
		kind.takesAnchor ? { mode: 'relative', value, unit, anchor: 'start' } : { mode: 'relative', value, unit };

	/**
	 * @param {{start: ?moment.Moment, end: ?moment.Moment}} window The window.
	 *
	 * @return {{start: ?string, end: ?string}} Each end of the window in the event timezone, or `null`.
	 */
	const format = ( window ) => ( {
		start: window.start ? window.start.format( FORMAT ) : null,
		end: window.end ? window.end.format( FORMAT ) : null,
	} );

	describe.each( [
		[ 'the sales window', SALES_WINDOW ],
		[ 'the sale price window', SALE_PRICE_WINDOW ],
	] )( 'for %s', ( label, kind ) => {
		it( 'should give each relative boundary the date it resolves to', () => {
			const rule = { start: relative( kind, 2, UNIT_WEEKS ), end: relative( kind, 1, UNIT_WEEKS ) };

			expect( format( getFormWindow( rule, EVENT_DATES, NO_FORM_DATES, kind ) ) ).toStrictEqual( {
				start: '2099-06-10 19:00:00',
				end: '2099-06-17 19:00:00',
			} );
		} );

		// The event starts on 2099-06-24 at 19:00 in New York, so 1 week before it is 2099-06-17.
		it( 'should give a specific boundary the date the form holds for it', () => {
			const rule = { start: { mode: 'specific' }, end: relative( kind, 1, UNIT_WEEKS ) };

			expect(
				format( getFormWindow( rule, EVENT_DATES, { start: '2099-06-16 00:00:00', end: null }, kind ) ).start
			).toBe( '2099-06-16 00:00:00' );
		} );

		it( 'should leave a specific boundary without a date empty', () => {
			const rule = { start: relative( kind, 2, UNIT_WEEKS ), end: { mode: 'specific' } };

			expect( getFormWindow( rule, EVENT_DATES, NO_FORM_DATES, kind ).end ).toBeNull();
		} );

		it.each( [
			[ 'a start with a cleared number read as NaN', 'start', Number.NaN ],
			[ 'an end with a cleared number read as NaN', 'end', Number.NaN ],
			[ 'an end with a cleared number kept as an empty string', 'end', '' ],
		] )( 'should leave out %s rather than count it from the event start', ( name, key, value ) => {
			// The boundary would fall on the event start.
			const rule = { start: relative( kind, 2, UNIT_WEEKS ), end: relative( kind, 1, UNIT_WEEKS ) };
			rule[ key ] = { ...rule[ key ], value };

			expect( getFormWindow( rule, EVENT_DATES, NO_FORM_DATES, kind )[ key ] ).toBeNull();
		} );

		it( 'should give no window without the event dates', () => {
			const rule = { start: relative( kind, 2, UNIT_WEEKS ), end: relative( kind, 1, UNIT_WEEKS ) };

			expect( getFormWindow( rule, null, NO_FORM_DATES, kind ) ).toBeNull();
		} );
	} );

	describe( 'for the sales window', () => {
		it( 'should judge the sales window by default', () => {
			const rule = {
				start: { mode: 'relative', value: 1, unit: UNIT_DAYS, anchor: 'end' },
				end: { mode: 'default' },
			};

			expect( format( getFormWindow( rule, EVENT_DATES, NO_FORM_DATES ) ) ).toStrictEqual( {
				start: '2099-06-23 22:00:00',
				end: '2099-06-24 19:00:00',
			} );
		} );

		it( 'should move a default start the form dates later than now to now', () => {
			jest.useFakeTimers( { now: new Date( '2099-06-03T12:00:00Z' ) } );
			const formDates = { start: '2099-06-20 00:00:00', end: null };

			const window = getFormWindow( DEFAULT_START_RULE, EVENT_DATES, formDates, SALES_WINDOW );

			expect( window.start.toISOString() ).toBe( '2099-06-03T12:00:00.000Z' );
		} );

		it( 'should leave a default start sent without a date to the ticket', () => {
			expect( getFormWindow( DEFAULT_START_RULE, EVENT_DATES, NO_FORM_DATES, SALES_WINDOW ).start ).toBeNull();
		} );
	} );

	describe( 'for the sale price window', () => {
		// 02:00 UTC on 2099-06-03 is still 2099-06-02 in New York.
		it( 'should count a now start from now, in the event timezone', () => {
			jest.useFakeTimers( { now: new Date( '2099-06-03T02:00:00Z' ) } );
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ) };

			expect( format( getFormWindow( rule, EVENT_DATES, NO_FORM_DATES, SALE_PRICE_WINDOW ) ).start ).toBe(
				'2099-06-02 22:00:00'
			);
		} );

		// The server stores a now start empty, whatever date the hidden field still holds.
		it( 'should count a now start from now even with a date in the form', () => {
			jest.useFakeTimers( { now: new Date( '2099-06-03T12:00:00Z' ) } );
			const rule = { start: { mode: 'now' }, end: relative( SALE_PRICE_WINDOW, 1, UNIT_WEEKS ) };
			const formDates = { start: '2099-01-01 00:00:00', end: null };

			expect( getFormWindow( rule, EVENT_DATES, formDates, SALE_PRICE_WINDOW ).start.toISOString() ).toBe(
				'2099-06-03T12:00:00.000Z'
			);
		} );
	} );
} );
