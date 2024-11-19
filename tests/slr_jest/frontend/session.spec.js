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
	pauseToCheckout,
	resume,
	getWatchedCheckoutControls,
	beaconInterrupt,
} from '@tec/tickets/seating/frontend/session';
import { watchCheckoutControls } from '../../../src/Tickets/Seating/app/frontend/session';
import { addFilter } from '@wordpress/hooks';

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
		expect(getResumeTimeoutId()).toBe(null);

		await resume();

		expect(isInterruptable()).toBe(true);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(102);
		expect(getHealthcheckTimeoutId()).toBe(103);
		expect(getResumeTimeoutId()).toBe(null);
	});

	it('should watch checkout button click', async () => {
		const actions = [
			'tec_tickets_seating_session_sync',
			'tec_tickets_seating_timer_pause_to_checkout',
		];
		const urlRegex = RegExp(
			`https://wordpress.test/wp-admin/admin-ajax\\.php\\?_ajaxNonce=1234567890&action=(${actions.join(
				'|'
			)})&token=test-token&postId=23`
		);
		fetch.mockIf(
			urlRegex,
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

		const element = dom.querySelector('#button-1');
		element.addEventListener = jest.fn();

		await watchCheckoutControls();

		expect(getWatchedCheckoutControls()).toHaveLength(1);
		expect(element.addEventListener).toHaveBeenCalledWith(
			'click',
			pauseToCheckout
		);
		expect(element.addEventListener).toHaveBeenCalledWith(
			'submit',
			pauseToCheckout
		);
	});

	it('should watch checkout form submit', async () => {
		const actions = [
			'tec_tickets_seating_session_sync',
			'tec_tickets_seating_timer_pause_to_checkout',
		];
		const urlRegex = RegExp(
			`https://wordpress.test/wp-admin/admin-ajax\\.php\\?_ajaxNonce=1234567890&action=(${actions.join(
				'|'
			)})&token=test-token&postId=23`
		);
		fetch.mockIf(
			urlRegex,
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

		const element = dom.querySelector('#my-custom-checkout-form');
		element.addEventListener = jest.fn();

		await watchCheckoutControls();

		expect(getWatchedCheckoutControls()).toHaveLength(1);
		expect(element.addEventListener).toHaveBeenCalledWith(
			'click',
			pauseToCheckout
		);
		expect(element.addEventListener).toHaveBeenCalledWith(
			'submit',
			pauseToCheckout
		);
	});

	it('pauseToCheckout', async () => {
		const actions = [
			'tec_tickets_seating_session_sync',
			'tec_tickets_seating_timer_pause_to_checkout',
		];
		const urlRegex = RegExp(
			`https://wordpress.test/wp-admin/admin-ajax\\.php\\?_ajaxNonce=1234567890&action=(${actions.join(
				'|'
			)})&token=test-token&postId=23`
		);
		fetch.mockIf(
			urlRegex,
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
		await pauseToCheckout();

		expect(isInterruptable()).toBe(false);
		expect(isStarted()).toBe(true);
		expect(isExpired()).toBe(false);
		expect(getCountdownTimeoutId()).toBe(null);
		expect(getHealthcheckTimeoutId()).toBe(null);
		expect(getResumeTimeoutId()).toBe(102);
		// Let's make sure it will set up to resume in 1 minute.
		expect(setTimeout).toHaveBeenCalledWith(resume, 60000);
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

		expect(window.addEventListener).toHaveBeenCalledWith(
			'beforeunload',
			beaconInterrupt
		);

		beaconInterrupt();

		expect(window.navigator.sendBeacon).toHaveBeenCalledWith(
			'https://wordpress.test/wp-admin/admin-ajax.php?_ajaxNonce=1234567890&action=tec_tickets_seating_session_interrupt_get_data&postId=23&token=test-token'
		);
	});
});
