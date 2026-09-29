import { getWindowError } from '@tec/tickets/relative-sale-dates/window-check';
import { SALES_END_BEFORE_START } from '@tec/tickets/relative-sale-dates/validation';

const UNIT_WEEKS = 604800;

const EVENT_DATES = { start: '2099-06-24 19:00:00', end: '2099-06-24 22:00:00', timezone: 'America/New_York' };

// Sales open at once and close a week before the event starts, on 2099-06-17 at 19:00.
const DEFAULT_START_RULE = {
	start: { mode: 'default' },
	end: { mode: 'relative', value: 1, unit: UNIT_WEEKS, anchor: 'start' },
};

describe( 'getWindowError', () => {
	it( 'should judge a default start by the start date the form sends, as the server does', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: '2099-06-20 00:00:00', end: null } ) ).toBe(
			SALES_END_BEFORE_START
		);
	} );

	it( 'should accept a default start the form sends before the end', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: '2099-06-01 00:00:00', end: null } ) ).toBeNull();
	} );

	it( 'should leave a default start sent without a date unjudged', () => {
		expect( getWindowError( DEFAULT_START_RULE, EVENT_DATES, { start: null, end: null } ) ).toBeNull();
	} );

	it( 'should reject a specific boundary sent without its date', () => {
		const rule = { start: { mode: 'specific' }, end: { mode: 'default' } };

		expect( getWindowError( rule, EVENT_DATES, { start: null, end: null } ) ).toBe( SALES_END_BEFORE_START );
	} );
} );
