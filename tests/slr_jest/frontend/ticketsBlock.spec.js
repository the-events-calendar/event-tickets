import {
	addModalEventListeners,
	cancelReservations,
} from '@tec/tickets/seating/frontend/ticketsBlock';
import { OUTBOUND_REMOVE_RESERVATIONS } from '@tec/tickets/seating/service';
require('jest-fetch-mock').enableMocks();

const apiModule = require('@tec/tickets/seating/service/api');

describe('Seat Selection Modal', () => {
	beforeEach(() => {
		fetch.resetMocks();
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
		apiModule.sendPostMessage = jest.fn();
		const mockDialogElement = document.createElement('div');

		cancelReservations(mockDialogElement);

		expect(apiModule.sendPostMessage).not.toHaveBeenCalled();
	});

	it('should dispatch message to remove reservations if iframe found', () => {
		apiModule.sendPostMessage = jest.fn();
		fetch.mockIf(
			/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
			JSON.stringify({ success: true })
		);
		// fetch.mockResponse( JSON.stringify({ success: true, data: { test: 100 } }) );

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

		expect(apiModule.sendPostMessage).toHaveBeenCalledTimes(1);
		expect(apiModule.sendPostMessage).toHaveBeenCalledWith(
			mockIframe,
			OUTBOUND_REMOVE_RESERVATIONS
		);
	});
});
