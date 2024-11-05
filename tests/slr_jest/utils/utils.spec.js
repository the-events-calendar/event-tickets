import {redirectTo} from '@tec/tickets/seating/utils';

describe( 'utils',  () => {
	describe('redirectTo', () => {
		let originalLocation;

		beforeEach(() => {
			originalLocation = window.location;

			// Create a mock location object.
			delete window.location;
			window.location = { href: '' };

			// Create a mock open function.
			window.open = jest.fn();
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

		it('should open the URL in a new tab with noopener and noreferrer', () => {
			const url = 'http://mytest.com';

			redirectTo(url, true);

			expect(window.open).toHaveBeenCalledTimes(1);
			expect(window.open).toHaveBeenCalledWith(url, '_blank', 'noopener,noreferrer');
		});

		it('should not open a new tab when newTab is false', () => {
			const url = 'http://mytest.com';

			redirectTo(url, false);

			expect(window.open).not.toHaveBeenCalled();
		});
	});
});
