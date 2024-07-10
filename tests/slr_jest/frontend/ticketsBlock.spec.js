import {
	addModalEventListeners,
	cancelReservations,
} from '@tec/tickets/seating/frontend/ticketsBlock';
import { OUTBOUND_REMOVE_RESERVATIONS } from '@tec/tickets/seating/service';
const serviceModule = require('@tec/tickets/seating/service');

let fetchMock;

describe('Seat Selection Modal', () => {
	beforeEach(() => {
		global.fetch = jest.fn(() =>
			Promise.resolve({
				json: () => Promise.resolve({ test: 100 }),
			}),
		);
	});

	it('should listen for hide and destroy events to remove the reservations', () => {
		window['tribe-tickets-seating-modal'] = {
			on: jest.fn(),
		};

		addModalEventListeners();

		expect(window['tribe-tickets-seating-modal'].on).toHaveBeenCalledTimes(
			2
		);
		expect(window['tribe-tickets-seating-modal'].on).toHaveBeenCalledWith(
			'hide',
			cancelReservations
		);
		expect(window['tribe-tickets-seating-modal'].on).toHaveBeenCalledWith(
			'destroy',
			cancelReservations
		);
	});

	it('should not dispatch message to remove reservations if iframe not found', () => {
		serviceModule.sendPostMessage = jest.fn();
		const mockDialogElement = document.createElement('div');

		cancelReservations(mockDialogElement);

		expect(serviceModule.sendPostMessage).not.toHaveBeenCalled();
	});

	it('should dispatch message to remove reservations if iframe', () => {
		serviceModule.sendPostMessage = jest.fn();
		const mockDialogElement = document.createElement('div');
		mockDialogElement.innerHTML = `
			<div class="tec-tickets-seating__iframe-container" data-token="test-token">
				<iframe class="tec-tickets-seating__iframe"></iframe>
			</div>
		`;
		const mockIframe = mockDialogElement.querySelector(
			'.tec-tickets-seating__iframe-container iframe.tec-tickets-seating__iframe'
		);

		cancelReservations(mockDialogElement);

		expect(serviceModule.sendPostMessage).toHaveBeenCalledTimes(1);
		expect(serviceModule.sendPostMessage).toHaveBeenCalledWith(
			mockIframe,
			OUTBOUND_REMOVE_RESERVATIONS
		);
	});
});
