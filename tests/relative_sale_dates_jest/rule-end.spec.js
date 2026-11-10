import { toRequestEnd } from '@tec/tickets/relative-sale-dates/rule-end';
import { SALE_PRICE_WINDOW, SALES_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';

describe( 'toRequestEnd', () => {
	it( 'should keep only the mode of an end that is not relative', () => {
		expect( toRequestEnd( { mode: 'specific', value: '2', unit: '86400', anchor: 'start' }, SALES_WINDOW ) ).toStrictEqual( {
			mode: 'specific',
		} );
	} );

	it( 'should read the number and unit of a relative end as integers, with its anchor', () => {
		expect( toRequestEnd( { mode: 'relative', value: '2', unit: '86400', anchor: 'end' }, SALES_WINDOW ) ).toStrictEqual( {
			mode: 'relative',
			value: 2,
			unit: 86400,
			anchor: 'end',
		} );
	} );

	it( 'should leave the anchor out of a kind that counts from the event start', () => {
		expect( toRequestEnd( { mode: 'relative', value: '3', unit: '604800', anchor: 'start' }, SALE_PRICE_WINDOW ) ).toStrictEqual( {
			mode: 'relative',
			value: 3,
			unit: 604800,
		} );
	} );

	// The server rejects a rule saved with a cleared number.
	it( 'should read a cleared number as NaN', () => {
		expect( toRequestEnd( { mode: 'relative', value: '', unit: '86400' }, SALE_PRICE_WINDOW ).value ).toBeNaN();
	} );
} );
