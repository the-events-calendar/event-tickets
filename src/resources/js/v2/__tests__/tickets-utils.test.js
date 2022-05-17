global.TribeCartEndpoint = { url : 'test' };
global.TribeCurrency = { formatting: JSON.stringify(formats) };

global.tribe = {
	tickets : {}
};

require( '../tickets-utils' );

const utils = tribe.tickets.utils;

test( 'Testing demo rest endpoint', () => {
	expect(utils.getRestEndpoint()).toBe("test");
} );