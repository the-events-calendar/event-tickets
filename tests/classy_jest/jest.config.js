const { defaults: tsjPreset } = require( 'ts-jest/presets' );

module.exports = {
	verbose: true,
	setupFiles: [ __dirname + '/jest.setup.ts' ],
	testEnvironment: 'jest-environment-jsdom-global',
	testMatch: [ '**/*.spec.ts', '**/*.spec.tsx' ],
	resolver: __dirname + '/jest-resolver.js',
	transform: {
		'^.+.tsx?$': [
			'ts-jest',
			{
				tsconfig: {
					allowImportingTsExtensions: true,
					allowJs: true,
					allowSyntheticDefaultImports: true,
					allowUmdGlobalAccess: true,
					alwaysStrict: true,
					exactOptionalPropertyTypes: true,
					baseUrl: ".",
					checkJs: false,
					esModuleInterop: true,
					jsx: "preserve",
					moduleResolution: "node10",
					noEmit: true,
					noImplicitReturns: true,
					sourceMap: true,
					target: "esnext"
				},
			},
		],
		'^.+\\.js$': 'babel-jest',
	},
	transformIgnorePatterns: [ '/node_modules/(?!client-zip|@wordpress/.*)' ],
	preset: 'ts-jest',
	moduleFileExtensions: [ 'ts', 'tsx', 'js', 'jsx' ],
	snapshotSerializers: [ '@emotion/jest/serializer' ],
	moduleNameMapper: {
		'@tec/common/(.*)$': '<rootDir>/../../common/src/resources/packages/$1',
		'@tec/common/classy/(.*)$': '<rootDir>/../../common/src/resources/packages/classy/$1',
	},
};
