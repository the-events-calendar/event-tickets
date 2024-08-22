import {
	reset,
	watchCheckoutControls,
	setHealthcheckLoopId,
	syncWithBackend,
} from '@tec/tickets/seating/frontend/session';
import { addFilter } from '@wordpress/hooks';

describe('Seat Selection Session', () => {
	beforeEach(() => {
		reset();
	});

	describe('watchCheckoutControls', () => {
		let dom;

		beforeEach(() => {
			// Filter the checkout controls selectors.
			addFilter(
				'tec.tickets.seating.frontend.session.checkoutControls',
				'test',
				() => '.test-checkout-control, .test-checkout-control-form'
			);
			// Mock the document to look for the checkout controls.
			dom = new DOMParser().parseFromString(
				`<html><body>
				<form class="test-checkout-control-form">
					<button class="test-checkout-control">Click me 1</button>
				</formcl>
			</body></html>`,
				'text/html'
			);
			global.clearTimeout = jest.fn();
			global.setTimeout = jest.fn();
			setHealthcheckLoopId(23);
		});

		it('should watch checkout controls to postpone timer backend sync on click', () => {
			watchCheckoutControls(dom);
			dom.querySelector('.test-checkout-control').click();

			expect(global.clearTimeout).toHaveBeenCalledWith(23);
			expect(global.setTimeout).toHaveBeenCalledWith(
				syncWithBackend,
				30000
			);
		});

		it('should watch checkout controls to postpone timer backend sync on submit', () => {
			watchCheckoutControls(dom);
			dom.querySelector('.test-checkout-control-form').submit();

			expect(global.clearTimeout).toHaveBeenCalledWith(23);
			expect(global.setTimeout).toHaveBeenCalledWith(
				syncWithBackend,
				30000
			);
		});
	});

	// TODO: Add tests for syncWithBackend, startCountdownLoop, startHealthCheckLoop and interrupt to make sure they are not running when expired.

	afterEach(() => {
		reset();
	});
});
