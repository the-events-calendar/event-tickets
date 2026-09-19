/**
 * Internal dependencies
 */
import { resolveV2Capacity } from '../normalize-rsvp-response';

describe( 'resolveV2Capacity', () => {
	it( 'returns the numeric capacity when capacity is a non-negative number', () => {
		expect( resolveV2Capacity( { capacity: '20' } ) ).toBe( 20 );
	} );

	it( 'falls back to stock when capacity is missing', () => {
		expect( resolveV2Capacity( { stock: '15' } ) ).toBe( 15 );
	} );

	it( 'falls through empty-string capacity to stock (unlimited payload)', () => {
		expect( resolveV2Capacity( { capacity: '', stock: -1 } ) ).toBe( '' );
	} );

	it( 'falls through empty-string capacity to a finite stock value', () => {
		expect( resolveV2Capacity( { capacity: '', stock: '15' } ) ).toBe( 15 );
	} );

	it( 'returns zero when capacity is explicitly 0', () => {
		expect( resolveV2Capacity( { capacity: 0, stock: -1 } ) ).toBe( 0 );
	} );

	it( 'returns an empty string when capacity is -1 (unlimited), even if stock is set', () => {
		expect( resolveV2Capacity( { capacity: -1, stock: '15' } ) ).toBe( '' );
	} );

	it( 'returns an empty string when capacity is -1 (unlimited)', () => {
		expect( resolveV2Capacity( { capacity: -1, stock: -1 } ) ).toBe( '' );
	} );

	it( 'returns an empty string when stock fallback is -1 (unlimited)', () => {
		expect( resolveV2Capacity( { stock: -1 } ) ).toBe( '' );
	} );

	it( 'returns an empty string when neither capacity nor stock is present', () => {
		expect( resolveV2Capacity( {} ) ).toBe( '' );
	} );

	it( 'ignores an unrelated stock_mode field rather than using it to short-circuit', () => {
		expect( resolveV2Capacity( { stock_mode: 'unlimited', capacity: 5 } ) ).toBe( 5 );
	} );
} );
