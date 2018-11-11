/**
 * Internal dependencies
 */
import { toLabel } from './../utils';

describe( 'toLabel', () => {
	test( 'Empty items', () => {
		expect( toLabel() ).toBe( '' )
		expect( toLabel( [] ) ).toBe( '' );
		expect( toLabel( [ '', false, 0 ] ) ).toBe( '' );
	} );

	test( 'List of names', () => {
		expect( toLabel( [ 'Modern' ] ) ).toBe( ' ( Modern ) ' );
		expect( toLabel( [ 'Modern', 'Tribe' ] ) ).toBe( ' ( Modern, Tribe ) ' );
	} );
} );
