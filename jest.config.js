const pkg = require('./package.json');

module.exports = {
	verbose: true,
	setupFiles: [
		'<rootDir>/jest.setup.js',
	],
	displayName: 'tickets',
	testEnvironment: 'jest-environment-jsdom-global',
	testMatch: pkg._filePath.jest.map((path) => `<rootDir>/${path}`),
	modulePathIgnorePatterns: ['<rootDir>/common'],
	moduleNameMapper: {
		'\\.(css|pcss)$': 'identity-obj-proxy',
		'\\.(svg)$': '<rootDir>/__mocks__/icons.js',
		// Seating feature.
		'^@tec/tickets/seating/tests/(.*)': '<rootDir>/tests/slr_jest/$1',
		'^@tec/tickets/seating/(.*)': '<rootDir>/src/Tickets/Seating/app/$1',
	},
	// Modules that should not be transformed by Jest.
	transformIgnorePatterns: [
		'/node_modules/(?!date-fns/)',
	],
	// Explicitly specify we want to use Babel for transformation
	transform: {
		"^.+\\.(js|jsx|ts|tsx)$": "babel-jest"
	}
};
