import moment from 'moment-timezone';
import {
	getSalePriceError,
	SALE_PRICE_ENDS_BEFORE_START,
	SALE_PRICE_OUTSIDE_SALES_WINDOW,
} from '@tec/tickets/relative-sale-dates/sale-price-check';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

// The event starts on 2099-06-24 at 19:00 in New York: 1 week before it is 2099-06-17, 2 weeks 2099-06-10.
const EVENT_DATES = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: 'America/New_York' };

const NO_FORM_DATES = { start: null, end: null };

/**
 * @param {string} start The sales start, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 * @param {string} end   The sales end, `YYYY-MM-DD HH:mm:ss` in the event timezone.
 *
 * @return {{start: moment.Moment, end: moment.Moment}} The ticket sales window.
 */
function salesWindow( start, end ) {
	return {
		start: moment.tz( start, EVENT_DATES.timezone ),
		end: moment.tz( end, EVENT_DATES.timezone ),
	};
}

// Sales open on 2099-06-08 and close when the event starts.
const SALES_WINDOW = salesWindow( '2099-06-08 09:00:00', '2099-06-24 19:00:00' );

/**
 * @param {number} value The number of units before the event starts.
 * @param {number} unit  The unit, in seconds.
 *
 * @return {Object} A relative boundary of the sale price window.
 */
function relative( value, unit ) {
	return { mode: 'relative', value, unit };
}

describe( 'getSalePriceError', () => {
	it( 'should accept a window inside the sales window that ends after the day it starts', () => {
		const rule = { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBeNull();
	} );

	it( 'should reject a window that ends on the day it starts', () => {
		const rule = { start: { mode: 'specific' }, end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, { start: '2099-06-17', end: null } ) ).toBe(
			SALE_PRICE_ENDS_BEFORE_START
		);
	} );

	it( 'should reject a window that ends before it starts', () => {
		const rule = { start: relative( 1, UNIT_WEEKS ), end: relative( 2, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBe( SALE_PRICE_ENDS_BEFORE_START );
	} );

	it( 'should reject a rule the server rejects, as the save does', () => {
		const rule = { start: { mode: 'now' }, end: relative( 31, UNIT_DAYS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBe( SALE_PRICE_ENDS_BEFORE_START );
	} );

	it( 'should judge the end of a now start against the day sales open', () => {
		const rule = { start: { mode: 'now' }, end: relative( 3, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBe( SALE_PRICE_ENDS_BEFORE_START );
	} );

	it( 'should judge the end of a specific start without a date against the day sales open', () => {
		const rule = { start: { mode: 'specific' }, end: relative( 3, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBe( SALE_PRICE_ENDS_BEFORE_START );
	} );

	it( 'should reject a start before sales open', () => {
		const rule = { start: relative( 20, UNIT_DAYS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, NO_FORM_DATES ) ).toBe( SALE_PRICE_OUTSIDE_SALES_WINDOW );
	} );

	it( 'should reject a start after sales close', () => {
		const rule = { start: { mode: 'specific' }, end: { mode: 'specific' } };

		expect(
			getSalePriceError( rule, EVENT_DATES, SALES_WINDOW, { start: '2099-06-25', end: '2099-06-26' } )
		).toBe( SALE_PRICE_OUTSIDE_SALES_WINDOW );
	} );

	it( 'should never judge a now start outside the sales window', () => {
		const rule = { start: { mode: 'now' }, end: relative( 1, UNIT_WEEKS ) };
		const lateSales = salesWindow( '2099-06-15 09:00:00', '2099-06-24 19:00:00' );

		expect( getSalePriceError( rule, EVENT_DATES, lateSales, NO_FORM_DATES ) ).toBeNull();
	} );

	it( 'should leave a window unjudged without both ends of the sales window, as the server does', () => {
		const rule = { start: relative( 1, UNIT_WEEKS ), end: relative( 2, UNIT_WEEKS ) };

		expect(
			getSalePriceError( rule, EVENT_DATES, { start: null, end: SALES_WINDOW.end }, NO_FORM_DATES )
		).toBeNull();
	} );
} );
