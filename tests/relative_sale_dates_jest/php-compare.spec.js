import { isAtLeast, isSalePriceKept } from '@tec/tickets/relative-sale-dates/php-compare';

describe( 'isAtLeast', () => {
	it.each( [
		[ 'a larger number', '10', '9', true ],
		[ 'an equal number written another way', '5.0', '5', true ],
		[ 'a smaller number', '4.99', '5', false ],
		[ 'a number in exponent notation', '1e1', '9', true ],
		[ 'a number with whitespace around it', ' 10 ', '9', true ],
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

	// `is_numeric()` reads none of them, so `'0x10' < '9'` as strings, where 16 would be at least 9.
	it.each( [
		[ 'hexadecimal', '0x10' ],
		[ 'binary', '0b1111' ],
		[ 'octal', '0o20' ],
	] )( 'should compare a %s literal as a string, as PHP does', ( label, value ) => {
		expect( isAtLeast( value, '9' ) ).toBe( false );
	} );
} );

describe( 'isSalePriceKept', () => {
	it.each( [
		[ 'added and lower than the price', true, '5', '10', true ],
		[ 'not added', false, '5', '10', false ],
		[ 'equal to the price', true, '10', '10.00', false ],
		[ 'higher than the price', true, '11', '10', false ],
	] )( 'should keep a sale price %s as the server does', ( label, isAdded, salePrice, regularPrice, expected ) => {
		expect( isSalePriceKept( isAdded, salePrice, regularPrice ) ).toBe( expected );
	} );
} );
