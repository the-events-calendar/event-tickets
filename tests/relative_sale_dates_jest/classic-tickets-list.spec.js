/**
 * @jest-environment ./tests/relative_sale_dates_jest/timezone-environment.js
 * @timezone America/New_York
 */
import { getListText } from '@tec/tickets/relative-sale-dates/classic/tickets-list';
import { resolveSaleWindow } from '@tec/tickets/relative-sale-dates/sale-window';

global.DateFormatter = require( 'php-date-formatter' );

const UNIT_HOURS = 3600;
const UNIT_WEEKS = 604800;
const SETTINGS = { format: 'F j, Y g:i a', dateSettings: {} };
const STORED = { start: '2099-01-02 09:00:00', end: '2099-01-03 17:30:00' };

/**
 * @param {Object} rule The sales window rule.
 *
 * @return {Object} The window the rule resolves to for a New York event on June 24, 2099 from 7 to 10 pm.
 */
function resolve( rule ) {
	return resolveSaleWindow( rule, '2099-06-24 19:00:00', '2099-06-24 22:00:00', 'America/New_York' );
}

describe( 'getListText', () => {
	it( 'should show the date a relative start and a default end work out to', () => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default' },
		};

		expect( getListText( rule, resolve( rule ), STORED, SETTINGS ) ).toBe(
			'June 10, 2099 7:00 pm - June 24, 2099 7:00 pm'
		);
	} );

	it( 'should show the time of a relative end that works out to the event start', () => {
		const rule = {
			start: { mode: 'default' },
			end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' },
		};

		expect( getListText( rule, resolve( rule ), STORED, SETTINGS ) ).toBe(
			'January 2, 2099 9:00 am - June 24, 2099 6:00 pm'
		);
	} );

	it( "should show a stored time that the browser's own clocks skip", () => {
		// This file runs in New York, which skips from 02:00 to 03:00 that night.
		const rule = { start: { mode: 'default' }, end: { mode: 'specific' } };
		const stored = { start: '2099-03-08 02:30:00', end: STORED.end };

		expect( getListText( rule, resolve( rule ), stored, SETTINGS ) ).toBe(
			'March 8, 2099 2:30 am - January 3, 2099 5:30 pm'
		);
	} );

	it( 'should keep the stored date of a default start and a specific end', () => {
		const rule = { start: { mode: 'default' }, end: { mode: 'specific' } };

		expect( getListText( rule, resolve( rule ), STORED, SETTINGS ) ).toBe(
			'January 2, 2099 9:00 am - January 3, 2099 5:30 pm'
		);
	} );

	it( 'should keep the stored dates when the event dates cannot be read', () => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default' },
		};

		expect( getListText( rule, null, STORED, SETTINGS ) ).toBe(
			'January 2, 2099 9:00 am - January 3, 2099 5:30 pm'
		);
	} );

	it( 'should end the text after the dash when there is no end date, as the server does', () => {
		const rule = { start: { mode: 'default' }, end: { mode: 'specific' } };

		expect( getListText( rule, resolve( rule ), { start: STORED.start, end: '' }, SETTINGS ) ).toBe(
			'January 2, 2099 9:00 am - '
		);
	} );
} );
