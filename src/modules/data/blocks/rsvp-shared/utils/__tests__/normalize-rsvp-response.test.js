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

	it( 'falls back to stock when capacity is negative (unlimited)', () => {
		expect( resolveV2Capacity( { capacity: -1, stock: '15' } ) ).toBe( 15 );
	} );

	it( 'returns an empty string when both capacity and stock are unlimited (-1)', () => {
		expect( resolveV2Capacity( { capacity: -1, stock: -1 } ) ).toBe( '' );
	} );

	it( 'returns an empty string when neither capacity nor stock is present', () => {
		expect( resolveV2Capacity( {} ) ).toBe( '' );
	} );

	it( 'ignores an unrelated stock_mode field rather than using it to short-circuit', () => {
		expect( resolveV2Capacity( { stock_mode: 'unlimited', capacity: 5 } ) ).toBe( 5 );
	} );
} );
