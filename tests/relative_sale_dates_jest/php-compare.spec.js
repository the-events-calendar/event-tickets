import { isAtLeast } from '@tec/tickets/relative-sale-dates/php-compare';

describe( 'isAtLeast', () => {
	it.each( [
		[ 'a larger number', '10', '9', true ],
		[ 'an equal number written another way', '5.0', '5', true ],
		[ 'a smaller number', '4.99', '5', false ],
	] )( 'should compare two numeric values as numbers: %s', ( label, value, other, expected ) => {
		expect( isAtLeast( value, other ) ).toBe( expected );
	} );

	// As strings, an empty value sorts before any other, and digits before letters.
	it.each( [
		[ 'a number against an empty value', '5', '', true ],
		[ 'an empty value against a number', '', '5', false ],
		[ 'a number against a word', '10', 'nine', false ],
	] )( 'should compare values that are not both numeric as strings: %s', ( label, value, other, expected ) => {
		expect( isAtLeast( value, other ) ).toBe( expected );
	} );
} );
