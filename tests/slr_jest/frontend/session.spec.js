import {
	reset,
	setTargetDom,
	syncOnLoad,
	isInterruptable,
	isStarted,
	isExpired,
	getHealthcheckTimeoutId,
	getCountdownTimeoutId,
	getResumeTimeoutId,
	pause,
	resume,
	getWatchedCheckoutControls,
	beaconInterrupt,
} from '@tec/tickets/seating/frontend/session';
import { watchCheckoutControls } from '../../../src/Tickets/Seating/app/frontend/session';
import {addFilter} from '@wordpress/hooks';

require('jest-fetch-mock').enableMocks();

describe('Seat Selection Session', () => {
	let dom;

	beforeEach(() => {
		fetch.resetMocks();
		jest.resetAllMocks();
		reset();
	});

	afterEach(() => {
		fetch.resetMocks();
		jest.resetAllMocks();
		reset();
	});

	it('should set up the timer element', async () => {
		fetch.mockIf(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_sync&token=test-token&postId=23',
			JSON.stringify({
				success: true,
				data: { secondsLeft: 30, timestamp: Date.now() / 1000 },
			})
		);
		let timeoutId = 100;
		global.setTimeout = jest.fn(() => timeoutId++);
		dom = getTestDocument('timer', (html) =>
			html
				.replaceAll('{{post_id}}', 23)
				.replaceAll('{{token}}', 'test-token')
		);
		setTargetDom(dom);

		await syncOnLoad();

		expect(isInterruptable()).toBe(true);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(100);
		expect(getHealthcheckTimeoutId()).toBe(101);
		expect(getResumeTimeoutId()).toBe(null);

		await pause();

		expect(isInterruptable()).toBe(false);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(null);
		expect(getHealthcheckTimeoutId()).toBe(null);
		expect(getResumeTimeoutId()).toBe(102);

		await resume();

		expect(isInterruptable()).toBe(true);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(103);
		expect(getHealthcheckTimeoutId()).toBe(104);
		expect(getResumeTimeoutId()).toBe(null);
	});

	it('should pause the timer on checkout control click', async () => {
		fetch.mockIf(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_sync&token=test-token&postId=23',
			JSON.stringify({
				success: true,
				data: { secondsLeft: 30, timestamp: Date.now() / 1000 },
			})
		);
		let timeoutId = 100;
		global.setTimeout = jest.fn(() => timeoutId++);
		dom = getTestDocument(
			'timer',
			(html) =>
				html
					.replaceAll('{{post_id}}', 23)
					.replaceAll('{{token}}', 'test-token') +
				'<button class="tribe-tickets__commerce-checkout-form-submit-button" id="button-1">Checkout</button>'
		);
		setTargetDom(dom);

		await syncOnLoad();
		await watchCheckoutControls();

		expect(getWatchedCheckoutControls()).toHaveLength(1);

		dom.querySelector('#button-1').click();

		expect(isInterruptable()).toBe(false);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(null);
		expect(getHealthcheckTimeoutId()).toBe(null);
		expect(getResumeTimeoutId()).toBe(102);
	});

	it('should pause the timer on checkout control submit', async () => {
		fetch.mockIf(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_sync&token=test-token&postId=23',
			JSON.stringify({
				success: true,
				data: { secondsLeft: 30, timestamp: Date.now() / 1000 },
			})
		);
		let timeoutId = 100;
		global.setTimeout = jest.fn(() => timeoutId++);
		dom = getTestDocument(
			'timer',
			(html) =>
				html
					.replaceAll('{{post_id}}', 23)
					.replaceAll('{{token}}', 'test-token') +
				'<form id="my-custom-checkout-form">' +
				'<button>Checkout</button>' +
				'</form>'
		);
		setTargetDom(dom);
		addFilter(
			'tec.tickets.seating.frontend.session.checkoutControls',
			'test',
			(selector) => selector + ', #my-custom-checkout-form'
		);

		await syncOnLoad();
		await watchCheckoutControls();

		expect(getWatchedCheckoutControls()).toHaveLength(1);

		dom.querySelector('#my-custom-checkout-form').submit();

		expect(isInterruptable()).toBe(false);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(null);
		expect(getHealthcheckTimeoutId()).toBe(null);
		expect(getResumeTimeoutId()).toBe(102);
	});

	it('should interrupt on page close', async () => {
		fetch.mockIf(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_sync&token=test-token&postId=23',
			JSON.stringify({
				success: true,
				data: { secondsLeft: 30, timestamp: Date.now() / 1000 },
			})
		);
		// Mock the window.navigator.sendBeacon function.
		window.navigator.sendBeacon = jest.fn();
		// Mock the window.addEventListener function.
		window.addEventListener = jest.fn();

		dom = getTestDocument('timer', (html) =>
			html
			.replaceAll('{{post_id}}', 23)
			.replaceAll('{{token}}', 'test-token')
		);
		setTargetDom(dom);

		await syncOnLoad();

		expect(window.addEventListener).toHaveBeenCalledWith('beforeunload', beaconInterrupt);

		beaconInterrupt();

		expect(window.navigator.sendBeacon).toHaveBeenCalledWith(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_interrupt_get_data&postId=23&token=test-token&auto=1'
		);
	});
});
