import { getSalePriceLengthText } from '@tec/tickets/relative-sale-dates/sale-price-length';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

// The event starts on 2099-06-24 at 19:00 in New York, so 1 week before it is 2099-06-17.
const EVENT_DATES = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: 'America/New_York' };

const NO_FORM_DATES = { start: null, end: null };

/**
 * @param {number} value The number of units before the event starts.
 * @param {number} unit  The unit, in seconds.
 *
 * @return {Object} A relative boundary of the sale price window.
 */
function relative( value, unit ) {
	return { mode: 'relative', value, unit };
}

describe( 'getSalePriceLengthText', () => {
	it( 'should count a whole number of weeks in weeks', () => {
		const rule = { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe(
			'Tickets on sale for 1 week'
		);
	} );

	it( 'should count any other length in days', () => {
		const rule = { start: relative( 10, UNIT_DAYS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe(
			'Tickets on sale for 3 days'
		);
	} );

	it( 'should count a now start from today', () => {
		const rule = { start: { mode: 'now' }, end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-06-03' ) ).toBe(
			'Tickets on sale for 2 weeks'
		);
	} );

	it( 'should count a specific boundary from the date the form holds for it', () => {
		const rule = { start: { mode: 'specific' }, end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, { start: '2099-06-16', end: null }, '2099-01-01' ) ).toBe(
			'Tickets on sale for 1 day'
		);
	} );

	it( 'should leave out a specific boundary without a date', () => {
		const rule = { start: relative( 2, UNIT_WEEKS ), end: { mode: 'specific' } };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe( '' );
	} );

	it( 'should leave out a relative boundary whose number was cleared', () => {
		const rule = { start: relative( Number.NaN, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe( '' );
	} );

	it.each( [
		[ 'a cleared number read as NaN', Number.NaN ],
		[ 'a cleared number kept as an empty string', '' ],
	] )( 'should leave out an end with %s rather than count it from the event start', ( label, value ) => {
		// A NaN or empty end subtracts as 0, so it would fall on the event start and read as 2 weeks.
		const rule = { start: relative( 2, UNIT_WEEKS ), end: relative( value, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe( '' );
	} );

	it( 'should leave out a relative number the server would reject', () => {
		// Sale_Price_Boundary takes 1 to 30; the classic number field allows typing past its max.
		const rule = { start: relative( 31, UNIT_DAYS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe( '' );
	} );

	it( 'should leave out a window that does not end after the day it starts', () => {
		const rule = { start: relative( 1, UNIT_WEEKS ), end: relative( 1, UNIT_WEEKS ) };

		expect( getSalePriceLengthText( rule, EVENT_DATES, NO_FORM_DATES, '2099-01-01' ) ).toBe( '' );
	} );

	it.each( [
		[ 'a cleared number read as NaN', Number.NaN ],
		[ 'a cleared number kept as an empty string', '' ],
	] )( 'should leave out a start with %s rather than count it from the event start', ( label, value ) => {
		// A NaN or empty start subtracts as 0, so it would fall on 2099-06-24 and read as 6 days to a specific end.
		const rule = { start: relative( value, UNIT_WEEKS ), end: { mode: 'specific' } };

		expect( getSalePriceLengthText( rule, EVENT_DATES, { start: null, end: '2099-06-30' }, '2099-01-01' ) ).toBe(
			''
		);
	} );
} );
