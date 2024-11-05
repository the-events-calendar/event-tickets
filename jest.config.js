const sharedConfig = require('@the-events-calendar/product-taskmaster/config/jest.config.js');
const pkg = require('./package.json');

module.exports = {
	...sharedConfig,
	displayName: 'tickets',
	testMatch: pkg._filePath.jest.map((path) => `<rootDir>/${path}`),
	modulePathIgnorePatterns: ['<rootDir>/common'],
	moduleNameMapper: {
		...sharedConfig.moduleNameMapper,
		// Seating feature.
		'^@tec/tickets/seating/tests/(.*)': '<rootDir>/tests/slr_jest/$1',
		'^@tec/tickets/seating/(.*)': '<rootDir>/src/Tickets/Seating/app/$1',
	},
};
