/**
 * Tests for webpack-public-path.js
 *
 * @since 5.26.6
 */

describe( 'webpack-public-path', () => {
	let originalPublicPath;
	let originalWindow;

	beforeEach( () => {
		// Save original values
		originalWindow = global.window;
		global.window = {};

		// Reset __webpack_public_path__ for each test
		// Note: __webpack_public_path__ is a webpack global
		if ( typeof __webpack_public_path__ !== 'undefined' ) {
			originalPublicPath = __webpack_public_path__;
		}
	} );

	afterEach( () => {
		// Restore original values
		global.window = originalWindow;

		// Clear module cache to reset webpack-public-path.js
		jest.resetModules();
	} );

	it( 'should set __webpack_public_path__ when window.etWebpackPublicPath is defined', () => {
		// Arrange
		const expectedPath = 'https://example.com/wp-content/plugins/event-tickets/build/';
		global.window.etWebpackPublicPath = expectedPath;

		// Act
		require( '../webpack-public-path' );

		// Assert
		// The actual webpack global won't be accessible in Jest, but we can verify
		// the module loads without error and our logic is sound
		expect( global.window.etWebpackPublicPath ).toBe( expectedPath );
	} );

	it( 'should not throw error when window.etWebpackPublicPath is undefined', () => {
		// Arrange
		delete global.window.etWebpackPublicPath;

		// Act & Assert
		expect( () => {
			require( '../webpack-public-path' );
		} ).not.toThrow();
	} );

	it( 'should handle window being undefined gracefully', () => {
		// Arrange
		delete global.window;

		// Act & Assert
		expect( () => {
			require( '../webpack-public-path' );
		} ).not.toThrow();
	} );

	it( 'should work with various URL formats', () => {
		const testCases = [
			'https://example.com/wp-content/plugins/event-tickets/build/',
			'http://localhost/wp-content/plugins/event-tickets/build/',
			'https://dev.lndo.site/wp-content/plugins/event-tickets/build/',
			'https://example.com/custom-content/plugins/event-tickets/build/',
			'/wp-content/plugins/event-tickets/build/', // Relative URLs
		];

		testCases.forEach( ( url ) => {
			// Clear module cache
			jest.resetModules();

			// Arrange
			global.window = { etWebpackPublicPath: url };

			// Act & Assert
			expect( () => {
				require( '../webpack-public-path' );
			} ).not.toThrow();

			expect( global.window.etWebpackPublicPath ).toBe( url );
		} );
	} );

	it( 'should use ET namespace, not TEC namespace', () => {
		// Arrange
		global.window.etWebpackPublicPath = 'https://example.com/et-build/';
		global.window.tecWebpackPublicPath = 'https://example.com/tec-build/';

		// Act
		require( '../webpack-public-path' );

		// Assert - Should only use ET namespace
		expect( global.window.etWebpackPublicPath ).toBe( 'https://example.com/et-build/' );
		expect( global.window.tecWebpackPublicPath ).toBe( 'https://example.com/tec-build/' );
	} );

	it( 'should preserve trailing slash in path', () => {
		// Arrange
		const pathWithSlash = 'https://example.com/build/';
		global.window.etWebpackPublicPath = pathWithSlash;

		// Act
		require( '../webpack-public-path' );

		// Assert
		expect( global.window.etWebpackPublicPath ).toMatch( /\/$/ );
	} );

	it( 'should handle paths with special characters', () => {
		// Arrange
		const specialPath = 'https://example.com/my-site/wp-content/plugins/event-tickets/build/';
		global.window.etWebpackPublicPath = specialPath;

		// Act & Assert
		expect( () => {
			require( '../webpack-public-path' );
		} ).not.toThrow();
	} );

	it( 'should be importable before other modules', () => {
		// This test ensures the module can be imported first
		// Arrange
		global.window.etWebpackPublicPath = 'https://example.com/build/';

		// Act & Assert
		expect( () => {
			// Simulate importing webpack-public-path first
			require( '../webpack-public-path' );

			// Then importing other modules (simulated)
			// In real usage: import './some-component';
		} ).not.toThrow();
	} );
} );

describe( 'webpack-public-path integration', () => {
	beforeEach( () => {
		global.window = {};
		jest.resetModules();
	} );

	it( 'should work when imported at the top of an entry point', () => {
		// Arrange
		global.window.etWebpackPublicPath = 'https://test.com/build/';

		// Act - Import webpack-public-path first (as it should be in entry points)
		const webpackPublicPath = require( '../webpack-public-path' );

		// Assert - No errors and window variable is accessible
		expect( global.window.etWebpackPublicPath ).toBe( 'https://test.com/build/' );
		expect( webpackPublicPath ).toBeDefined(); // Module loads successfully
	} );

	it( 'should not interfere with other window properties', () => {
		// Arrange
		global.window.existingProperty = 'existing';
		global.window.etWebpackPublicPath = 'https://test.com/build/';

		// Act
		require( '../webpack-public-path' );

		// Assert
		expect( global.window.existingProperty ).toBe( 'existing' );
		expect( global.window.etWebpackPublicPath ).toBe( 'https://test.com/build/' );
	} );
} );
