global.TribeCartEndpoint = { url : 'test' };

const formats = {
	'default' : {
		"symbol":"$",
		"placement":"prefix",
		"decimal_point":".",
		"thousands_sep":",",
		"number_of_decimals":2
	},
	'german' : {
		"symbol":"$",
		"placement":"prefix",
		"decimal_point":",",
		"thousands_sep":".",
		"number_of_decimals":2
	},

};
global.TribeCurrency = { formatting: JSON.stringify(formats) };

global.tribe = {
	tickets : {}
};

require( '../tickets-utils' );

const utils = tribe.tickets.utils;

test( 'Testing demo rest endpoint', () => {
	expect(utils.getRestEndpoint()).toBe("test");
} );

test( 'Testing formats are not empty', () => {
	expect( utils.getCurrencyFormatting('default') ).not.toBe( {} );
} );
} );