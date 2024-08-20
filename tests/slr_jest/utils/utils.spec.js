import {redirectTo} from '@tec/tickets/seating/utils';

describe( 'utils',  () => {
	describe('redirectTo', () => {
		let originalLocation;

		beforeEach(() => {
			originalLocation = window.location;

			// Create a mock location object.
			delete window.location;
			window.location = { href: '' };
		});

		afterEach(() => {
			// Restore the original window.location after each test
			window.location = originalLocation;
		});

		it('should set window.location.href to the given URL', () => {
			const url = 'http://mytest.com';

			redirectTo(url);

			expect(window.location.href).toBe(url);
		});
	});
} );
