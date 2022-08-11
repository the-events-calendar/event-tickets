global.TribeCartEndpoint = { url : 'test' };

// Follows the formatting found here: `Tribe__Tickets__Tickets::get_asset_localize_data_for_currencies()`
const formats = {
	'default' : {
		"symbol":"$",
		"placement":"prefix",
		"decimal_point":".",
		"thousands_sep":",",
		"number_of_decimals":2
	},
	'german' : {
		"symbol":"€",
		"placement":"prefix",
		"decimal_point":",",
		"thousands_sep":".",
		"number_of_decimals":2
	},
	'special' : {
		"symbol":"€",
		"placement":"prefix",
		"decimal_point":".",
		"thousands_sep":".",
		"number_of_decimals":3
	},

};
global.TribeCurrency = { formatting: JSON.stringify(formats) };

global.tribe = {
	tickets : {}
};

require( '../tickets-utils' );

const utils = tribe.tickets.utils;

test( 'Dummy rest endpoint is working', () => {
	expect(utils.getRestEndpoint()).toBe("test");
} );

test( 'Formats are not empty', () => {
	expect( utils.getCurrencyFormatting('default') ).not.toBe( {} );
} );

describe( 'Format testing', () => {

	const dataset = [
		{ format: 'default', number: '12.22', output: '12.22' },
		{ format: 'default', number: '1112.22', output: '1,112.22' },
		{ format: 'default', number: '1111111.00', output: '1,111,111.00' },
		{ format: 'german', number: '12.22', output: '12,22' },
		{ format: 'german', number: '1212.22', output: '1.212,22' },
		{ format: 'german', number: '393939.00', output: '393.939,00' },
		{ format: 'german', number: '2011.00', output: '2.011,00' },
	];

	describe( 'Testing Number format', () => {
		test.each(dataset)('values :( $format, $number, $output )', ({ format, number, output }) => {
			expect(utils.numberFormat( number, format )).toBe(output);
		});
	} );

	describe( 'Testing Clean Number', () => {
		test.each(dataset)('values : ( $format, $number, $output )', ({ format, number, output }) => {
			expect(utils.cleanNumber( output, format )).toBe(number);
		});
	} );
} );

describe( 'Clean number extended tests', () => {
	const dataset = [
		{ formatName: 'default', formatted: '12', raw: '12' },
		{ formatName: 'default', formatted: '1,000', raw: '1000' },
		{ formatName: 'default', formatted: '1,000.00', raw: '1000.00' },
		{ formatName: 'default', formatted: '999', raw: '999' },
		{ formatName: 'german', formatted: '12', raw: '12' },
		{ formatName: 'german', formatted: '1.000', raw: '1000' },
		{ formatName: 'german', formatted: '1.000,00', raw: '1000.00' },
		{ formatName: 'german', formatted: '999', raw: '999' },
		{ formatName: 'german', formatted: '99.999.999,99', raw: '99999999.99' },
		{ formatName: 'german', formatted: '99.999.999', raw: '99999999' },
	];

	describe( 'Testing Clean Number', () => {
		test.each(dataset)( 'values : ( $formatName, $formatted, $raw )', ( { formatName, formatted, raw } ) => {
			expect(utils.cleanNumber( formatted, formatName )).toBe(raw);
		} );
	});

	it( 'Should return same number if no separator found', () => {
		expect(utils.cleanNumber( '1111', 'default' )).toBe('1111');
	} );
} );


describe( 'Format number extended tests', () => {
	const dataset = [
		{ formatName: 'default', formatted: '12.00', raw: '12' },
		{ formatName: 'default', formatted: '1,000.00', raw: '1000' },
		{ formatName: 'default', formatted: '999.00', raw: '999' },
		{ formatName: 'default', formatted: '999.33', raw: '999.333' },
		{ formatName: 'default', formatted: '999.79', raw: '999.7888' },
		{ formatName: 'special', formatted: '1.000', raw: '1' },
		{ formatName: 'special', formatted: '1.000.000', raw: '1000' },
	];

	describe( 'Testing Format Number', () => {
		test.each(dataset)( 'values : ( $formatName, $formatted, $raw )', ( { formatName, formatted, raw } ) => {
			expect(utils.numberFormat( raw, formatName )).toBe(formatted);
		} );
	});

	it( 'Should return false if invalid format given', () => {
		expect( utils.numberFormat( '111', 'invalid-format' ) ).toBe(false);
	} );
} );

describe( 'Testing Shared Capacity Calculations', () => {

	// result should always the be minimum value between `target_available` and `maxAvailable - addedtToCart`.
	const dataset = [
		{ targetQty: 2, targetAvailable: 2, maxAvailable: 2, addedToCart: 0, result: 2 },
		{ targetQty: 1, targetAvailable: 1, maxAvailable: 2, addedToCart: 0, result: 1 },
		{ targetQty: 3, targetAvailable: 2, maxAvailable: 5, addedToCart: 0, result: 2 },
		{ targetQty: 3, targetAvailable: 3, maxAvailable: 2, addedToCart: 0, result: 2 },
		{ targetQty: 1, targetAvailable: 1, maxAvailable: 2, addedToCart: 2, result: 0 },
		{ targetQty: 3, targetAvailable: 3, maxAvailable: 2, addedToCart: 2, result: 0 },
		{ targetQty: 4, targetAvailable: 4, maxAvailable: 6, addedToCart: 3, result: 3 },
		{ targetQty: 4, targetAvailable: 5, maxAvailable: 3, addedToCart: 0, result: 3 },
		{ targetQty: 4, targetAvailable: 5, maxAvailable: 3, addedToCart: 1, result: 2 },
		{ targetQty: 4, targetAvailable: 5, maxAvailable: 3, addedToCart: 2, result: 1 },
		{ targetQty: 4, targetAvailable: 5, maxAvailable: 3, addedToCart: 3, result: 0 },
	];

	describe( 'Testing Shared Cap combinations', () => {
		test.each(dataset)( 'values : ( $targetQty, $targetAvailable, $maxAvailable, $addedToCart )', ( { targetQty, targetAvailable, maxAvailable, addedToCart, result } ) => {
			expect(utils.calculateSharedCap( targetQty, targetAvailable, maxAvailable, addedToCart )).toBe(result);
		} );
	});
} );