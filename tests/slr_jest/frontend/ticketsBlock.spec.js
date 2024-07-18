import {
	addModalEventListeners,
	bootstrapIframe,
	cancelReservations,
} from '@tec/tickets/seating/frontend/ticketsBlock';
import {
	INBOUND_SEATS_SELECTED,
	OUTBOUND_REMOVE_RESERVATIONS,
} from '@tec/tickets/seating/service';
import { getHandlerForAction } from '@tec/tickets/seating/service/api';
import { setToken } from '@tec/tickets/seating/service/api/state';
import { applyFilters } from '@wordpress/hooks';

require('jest-fetch-mock').enableMocks();

const apiModule = require('@tec/tickets/seating/service/api');
const iframeModule = require('@tec/tickets/seating/service/iframe');

describe('Seat Selection Modal', () => {
	beforeEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	describe('reservation cancellation', () => {
		it('should listen for hide and destroy events to remove the reservations', () => {
			window['tribe-tickets-seating-modal'] = {
				on: jest.fn(),
			};

			addModalEventListeners();

			expect(
				window['tribe-tickets-seating-modal'].on
			).toHaveBeenCalledTimes(2);
			expect(
				window['tribe-tickets-seating-modal'].on
			).toHaveBeenCalledWith('hide', cancelReservations);
			expect(
				window['tribe-tickets-seating-modal'].on
			).toHaveBeenCalledWith('destroy', cancelReservations);
		});

		it('should not sendMessage to remove reservations if iframe not found', async () => {
			apiModule.sendPostMessage = jest.fn();
			const mockDialogElement = document.createElement('div');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			await cancelReservations(mockDialogElement);

			expect(apiModule.sendPostMessage).not.toHaveBeenCalled();
			expect(fetch).toBeCalled();
		});

		it('should sendMessage to remove reservations if iframe found', async () => {
			apiModule.sendPostMessage = jest.fn();
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			const mockDialogElement = document.createElement('div');
			mockDialogElement.innerHTML = `
			<div class="tec-tickets-seating__iframe-container" data-token="test-token">
				<iframe class="tec-tickets-seating__iframe"></iframe>
			</div>
		`;
			const mockIframe = mockDialogElement.querySelector(
				'.tec-tickets-seating__iframe-container iframe.tec-tickets-seating__iframe'
			);

			await cancelReservations(mockDialogElement);

			expect(apiModule.sendPostMessage).toHaveBeenCalledTimes(1);
			expect(apiModule.sendPostMessage).toHaveBeenCalledWith(
				mockIframe,
				OUTBOUND_REMOVE_RESERVATIONS
			);
			expect(fetch).toBeCalled();
		});
	});

	describe('filters', () => {
		it('should filter getTickets IDs correctly with no IDs', () => {
			const ids = applyFilters(
				'tec.tickets.tickets-block.getTickets',
				[]
			);

			expect(ids).toEqual([23, 89, 66]);
		});

		it('should filter getTickets IDs correctly when there are IDs', () => {
			const ids = applyFilters(
				'tec.tickets.tickets-block.getTickets',
				[1, 2, 3]
			);

			expect(ids).toEqual([23, 89, 66]);
		});
	});

	describe('selection push to backend', () => {
		function getTestDocument() {
			return new DOMParser().parseFromString(
				`<div class="event-tickets">
					<div class="tec-tickets-seating__iframe-container" data-token="test-token">
						<iframe class="tec-tickets-seating__iframe"></iframe>
					</div>
					<div class="tec-tickets-seating__ticket-rows">
					</div>
					<div>
						<p class="tec-tickets-seating__total-price"></p>
						<p class="tec-tickets-seating__total-text"></p>
					</div>
					<div class="tec-tickets-seating__modal">
						<button class="tec-tickets-seating__sidebar-control--confirm">
							Check Out
						</button>
					</div>
				</div>`,
				'text/html'
			);
		}

		iframeModule.initServiceIframe = jest.fn(() => true);

		// Fire the INBOUND_SEATS_SELECTED  action with correct payload.
		it('should handle adding seat selection correctly', async () => {
			const dom = getTestDocument();
			setToken('test-token');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			await bootstrapIframe(dom);
			const handler = getHandlerForAction(INBOUND_SEATS_SELECTED);

			// No seats selected yet.
			const firstPayload = [];

			await handler(firstPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// A first seat selection for A-1.
			const secondPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
			];

			fetch.resetMocks();
			await handler(secondPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// A second seat selection for A-2.
			const thirdPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#ff0000',
					seatLabel: 'A-2',
					reservationId: 'uuid-reservation-1',
				},
			];

			fetch.resetMocks();
			await handler(thirdPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
								{
									reservationId: 'uuid-reservation-1',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-2',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// A selection for B-15, a seat associated with a different ticket.
			const fourthPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#ff0000',
					seatLabel: 'A-2',
					reservationId: 'uuid-reservation-1',
				},
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
			];

			fetch.resetMocks();
			await handler(fourthPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
								{
									reservationId: 'uuid-reservation-1',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-2',
								},
							],
							89: [
								{
									reservationId: 'uuid-reservation-3',
									seatTypeId: 'uuid-seat-type-1',
									seatLabel: 'B-15',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// Finally another seat selection for C-23.
			const fifthPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#ff0000',
					seatLabel: 'A-2',
					reservationId: 'uuid-reservation-1',
				},
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
				{
					seatTypeId: 'uuid-seat-type-2',
					ticketId: 66,
					seatColor: '#00ff00',
					seatLabel: 'C-23',
					reservationId: 'uuid-reservation-4',
				},
			];

			fetch.resetMocks();
			await handler(fifthPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
								{
									reservationId: 'uuid-reservation-1',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-2',
								},
							],
							89: [
								{
									reservationId: 'uuid-reservation-3',
									seatTypeId: 'uuid-seat-type-1',
									seatLabel: 'B-15',
								},
							],
							66: [
								{
									reservationId: 'uuid-reservation-4',
									seatTypeId: 'uuid-seat-type-2',
									seatLabel: 'C-23',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);
		});

		it('should handle removing seat selection correctly', async () => {
			const dom = getTestDocument();
			setToken('test-token');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			await bootstrapIframe(dom);
			const handler = getHandlerForAction(INBOUND_SEATS_SELECTED);

			// The first payload adds seats for A-1, A-2, B-15, C-23.
			const setupPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#ff0000',
					seatLabel: 'A-2',
					reservationId: 'uuid-reservation-1',
				},
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
				{
					seatTypeId: 'uuid-seat-type-2',
					ticketId: 66,
					seatColor: '#00ff00',
					seatLabel: 'C-23',
					reservationId: 'uuid-reservation-4',
				},
			];

			await handler(setupPayload);
			// The verification of this working correctly is done in the previous test; this is just setup code.

			// The first payload removes A-2.
			const firstPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
				{
					seatTypeId: 'uuid-seat-type-2',
					ticketId: 66,
					seatColor: '#00ff00',
					seatLabel: 'C-23',
					reservationId: 'uuid-reservation-4',
				},
			];

			fetch.resetMocks();
			await handler(firstPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
							],
							89: [
								{
									reservationId: 'uuid-reservation-3',
									seatTypeId: 'uuid-seat-type-1',
									seatLabel: 'B-15',
								},
							],
							66: [
								{
									reservationId: 'uuid-reservation-4',
									seatTypeId: 'uuid-seat-type-2',
									seatLabel: 'C-23',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// The second payload removes C-23.
			const secondPayload = [
				{
					seatTypeId: 'uuid-seat-type-0',
					ticketId: 23,
					seatColor: '#00ff00',
					seatLabel: 'A-1',
					reservationId: 'uuid-reservation-0',
				},
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
			];

			fetch.resetMocks();
			await handler(secondPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							23: [
								{
									reservationId: 'uuid-reservation-0',
									seatTypeId: 'uuid-seat-type-0',
									seatLabel: 'A-1',
								},
							],
							89: [
								{
									reservationId: 'uuid-reservation-3',
									seatTypeId: 'uuid-seat-type-1',
									seatLabel: 'B-15',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// The third payload removes A-1.
			const thirdPayload = [
				{
					seatTypeId: 'uuid-seat-type-1',
					ticketId: 89,
					seatColor: '#00ff00',
					seatLabel: 'B-15',
					reservationId: 'uuid-reservation-3',
				},
			];

			fetch.resetMocks();
			await handler(thirdPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {
							89: [
								{
									reservationId: 'uuid-reservation-3',
									seatTypeId: 'uuid-seat-type-1',
									seatLabel: 'B-15',
								},
							],
						},
					}),
					signal: expect.any(AbortSignal),
				}
			);

			// The fourth payload removes B-15, the last selected ticket.
			const fourthPayload = [];

			fetch.resetMocks();
			await handler(fourthPayload);

			expect(dom.querySelector('.event-tickets')).toMatchSnapshot();
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=tec_tickets_seating_post_reservations',
				{
					method: 'POST',
					body: JSON.stringify({
						token: 'test-token',
						reservations: {},
					}),
					signal: expect.any(AbortSignal),
				}
			);
		});
	});
});
