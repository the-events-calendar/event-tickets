import {
	registerAction,
	reset,
	setToken,
} from '@tec/tickets/seating/service/api/state';
import {
	catchMessage,
	getHandlerQueue,
	sendPostMessage,
	emptyHandlerQueue,
	getAssociatedEventsUrl,
} from '@tec/tickets/seating/service/api';

describe('Service API', () => {
	beforeEach(() => {
		reset();
		emptyHandlerQueue();
	});

	afterAll(() => {
		reset();
		emptyHandlerQueue();
	});

	it('should reject messages missing origin', () => {
		setToken('test-token');
		const event = {
			origin: null,
			data: {
				token: 'test-token',
				action: 'test-action',
				data: 'test-data',
			},
		};

		const callback = jest.fn();
		registerAction('test-action', callback);
		catchMessage(event);

		expect(callback).not.toHaveBeenCalled();
	});

	it('should reject messages with origin not matching base URL', () => {
		setToken('test-token');
		const event = {
			origin: 'https://wordpress.test/foo',
			data: {
				token: 'test-token',
				action: 'test-action',
				data: 'test-data',
			},
		};

		const callback = jest.fn();
		registerAction('test-action', callback);
		catchMessage(event);

		expect(callback).not.toHaveBeenCalled();
	});

	it('should reject messages with missing token', () => {
		const event = {
			origin: 'https://wordpress.test',
			data: {
				action: 'test-action',
				data: 'test-data',
			},
		};

		const callback = jest.fn();
		registerAction('test-action', callback);
		catchMessage(event);

		expect(callback).not.toHaveBeenCalled();
	});

	it('should reject messages with mismatching token', () => {
		setToken('test-token');
		const event = {
			origin: 'https://wordpress.test',
			data: {
				token: 'test-token-mismatch',
				action: 'test-action',
				data: 'test-data',
			},
		};

		const callback = jest.fn();
		registerAction('test-action', callback);
		catchMessage(event);

		expect(callback).not.toHaveBeenCalled();
	});

	it('should allow catching messages', () => {
		setToken('test-token');
		const message = new MessageEvent('message', {
			origin: 'https://wordpress.test',
			data: {
				action: 'test-action',
				token: 'test-token',
				data: {
					test: 'data',
				},
			},
		});

		const callback = jest.fn();
		registerAction('test-action', callback);
		catchMessage(message);

		expect(callback).toHaveBeenCalledWith({
			test: 'data',
		});
	});

	it('should allow sending messages to an iframe', () => {
		const iframe = {
			closest: jest.fn().mockReturnValue({
				dataset: {
					token: 'test-token',
				},
			}),
			contentWindow: {
				postMessage: jest.fn(),
			},
		};

		sendPostMessage(iframe, 'test-action', {
			test: 'data',
		});

		expect(iframe.contentWindow.postMessage).toHaveBeenCalledWith(
			{
				action: 'test-action',
				token: 'test-token',
				data: {
					test: 'data',
				},
			},
			'https://wordpress.test'
		);
	});

	it('should run actions for catched messages sequentially', async () => {
		setToken('test-token');
		const iframe = {
			closest: jest.fn().mockReturnValue({
				dataset: {
					token: 'test-token',
				},
			}),
			contentWindow: {
				postMessage: jest.fn(),
			},
		};

		const callback1 = jest.fn();
		const callback2 = jest.fn();
		const callback3 = jest.fn();
		registerAction('test-action-1', callback1);
		registerAction('test-action-2', callback2);
		registerAction('test-action-3', callback3);

		expect(callback1).not.toHaveBeenCalled();
		expect(callback2).not.toHaveBeenCalled();
		expect(callback3).not.toHaveBeenCalled();

		catchMessage(
			new MessageEvent('message', {
				origin: 'https://wordpress.test',
				data: {
					action: 'test-action-2',
					token: 'test-token',
					data: {
						test: 'data-2',
					},
				},
			})
		);

		expect(callback1).not.toHaveBeenCalled();
		expect(callback2).toHaveBeenCalledWith({
			test: 'data-2',
		});
		expect(callback3).not.toHaveBeenCalled();

		catchMessage(
			new MessageEvent('message', {
				origin: 'https://wordpress.test',
				data: {
					action: 'test-action-3',
					token: 'test-token',
					data: {
						test: 'data-3',
					},
				},
			})
		);

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(callback1).not.toHaveBeenCalled();
		expect(callback2).toHaveBeenCalledWith({
			test: 'data-2',
		});
		expect(callback3).toHaveBeenCalledWith({
			test: 'data-3',
		});

		catchMessage(
			new MessageEvent('message', {
				origin: 'https://wordpress.test',
				data: {
					action: 'test-action-1',
					token: 'test-token',
					data: {
						test: 'data-1',
					},
				},
			})
		);

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(callback1).toHaveBeenCalledWith({
			test: 'data-1',
		});
		expect(callback2).toHaveBeenCalledWith({
			test: 'data-2',
		});
		expect(callback3).toHaveBeenCalledWith({
			test: 'data-3',
		});
	});

	it('should wait resolution and call async handlers in sequence', async () => {
		setToken('test-token');

		let resolvePromise1;
		let resolvePromise2;
		let resolvePromise3;

		const asyncHandler1 = async () => {
			return new Promise((resolve) => {
				resolvePromise1 = resolve;
			});
		};
		const asyncHandler2 = async () => {
			return new Promise((resolve) => {
				resolvePromise2 = resolve;
			});
		};
		const asyncHandler3 = async () => {
			return new Promise((resolve) => {
				resolvePromise3 = resolve;
			});
		};

		registerAction('test-action-1', asyncHandler1);
		registerAction('test-action-2', asyncHandler2);
		registerAction('test-action-3', asyncHandler3);

		expect(getHandlerQueue()).toStrictEqual([]);

		const messageEvent1 = new MessageEvent('message', {
			origin: 'https://wordpress.test',
			data: {
				action: 'test-action-1',
				token: 'test-token',
				data: {
					test: 'data-1',
				},
			},
		});
		catchMessage(messageEvent1);

		expect(getHandlerQueue()).toStrictEqual([
			['test-action-1', asyncHandler1, messageEvent1],
		]);

		const messageEvent2 = new MessageEvent('message', {
			origin: 'https://wordpress.test',
			data: {
				action: 'test-action-2',
				token: 'test-token',
				data: {
					test: 'data-2',
				},
			},
		});
		catchMessage(messageEvent2);

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(getHandlerQueue()).toStrictEqual([
			['test-action-1', asyncHandler1, messageEvent1],
			['test-action-2', asyncHandler2, messageEvent2],
		]);

		resolvePromise1();

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(getHandlerQueue()).toStrictEqual([
			['test-action-2', asyncHandler2, messageEvent2],
		]);

		const messageEvent3 = new MessageEvent('message', {
			origin: 'https://wordpress.test',
			data: {
				action: 'test-action-3',
				token: 'test-token',
				data: {
					test: 'data-3',
				},
			},
		});
		catchMessage(messageEvent3);

		expect(getHandlerQueue()).toStrictEqual([
			['test-action-2', asyncHandler2, messageEvent2],
			['test-action-3', asyncHandler3, messageEvent3],
		]);

		resolvePromise2();

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(getHandlerQueue()).toStrictEqual([
			['test-action-3', asyncHandler3, messageEvent3],
		]);

		resolvePromise3();

		// Wait 10 ms to let the queue process asynchronously.
		await new Promise((resolve) => setTimeout(resolve, 10));

		expect(getHandlerQueue()).toStrictEqual([]);
	});

	it( 'should generate proper getassociatedeventsurl with layout ID', () => {
		const url = getAssociatedEventsUrl('layout-1');
		expect( url ).toMatchSnapshot();
	} );

	it( 'should return original getassociatedeventsurl without layout ID', () => {
		const url = getAssociatedEventsUrl();
		expect( url ).toMatchSnapshot();
	} );
});
