import { getListText } from '@tec/tickets/relative-sale-dates/classic/tickets-list';
import { resolveSaleWindow } from '@tec/tickets/relative-sale-dates/sale-window';

global.DateFormatter = require( 'php-date-formatter' );

const UNIT_WEEKS = 604800;
const SETTINGS = { format: 'F j, Y', dateSettings: {} };
const STORED = { start: '2099-01-02', end: '2099-01-03' };

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

		expect( getListText( rule, resolve( rule ), STORED, SETTINGS ) ).toBe( 'June 10, 2099 - June 24, 2099' );
	} );

	it( 'should keep the stored date of a default start and a specific end', () => {
		const rule = { start: { mode: 'default' }, end: { mode: 'specific' } };

		expect( getListText( rule, resolve( rule ), STORED, SETTINGS ) ).toBe( 'January 2, 2099 - January 3, 2099' );
	} );

	it( 'should keep the stored dates when the event dates cannot be read', () => {
		const rule = {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default' },
		};

		expect( getListText( rule, null, STORED, SETTINGS ) ).toBe( 'January 2, 2099 - January 3, 2099' );
	} );

	it( 'should end the text after the dash when there is no end date, as the server does', () => {
		const rule = { start: { mode: 'default' }, end: { mode: 'specific' } };

		expect( getListText( rule, resolve( rule ), { start: STORED.start, end: '' }, SETTINGS ) ).toBe(
			'January 2, 2099 - '
		);
	} );
} );
