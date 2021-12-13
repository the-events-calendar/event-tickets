var sharedConfig = require( '@the-events-calendar/product-taskmaster/config/jest.config.js' );
var pkg = require( './package.json' );

module.exports = {
	...sharedConfig,
	displayName: 'tickets',
	testMatch: pkg._filePath.jest.map( ( path ) => `<rootDir>/${ path }` ),
};
