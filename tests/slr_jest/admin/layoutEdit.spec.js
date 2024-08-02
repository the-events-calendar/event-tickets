import { init } from '@tec/tickets/seating/admin/layoutEdit';
import {
	getHandlerForAction,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	SEAT_TYPES_UPDATED,
} from '@tec/tickets/seating/service/api';
import {
	ACTION_DELETE_RESERVATIONS,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ACTION_SEAT_TYPES_UPDATED,
} from '@tec/tickets/seating/ajax';

const apiModule = require('@tec/tickets/seating/service/api');

require('jest-fetch-mock').enableMocks();

describe('Layouts Edit', () => {
	beforeEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();

		// Mock the service functionality to establish readiness.
		apiModule.establishReadiness = jest.fn(() => true);
	});

	afterEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	function getTestDocument() {
		return new DOMParser().parseFromString(
			`<div class="event-tickets">
					<div class="tec-tickets-seating__iframe-container" data-token="test-token">
						<iframe class="tec-tickets-seating__iframe"></iframe>
					</div>
				</div>`,
			'text/html'
		);
	}

	describe('handleReservationsDeleted', () => {
		it('should not send requests to the backed on empty reservation UUIds', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');

			await init(dom);

			const handler = getHandlerForAction(RESERVATIONS_DELETED);
			const result = await handler([]);

			expect(fetch).not.toBeCalled();
			expect(result).toBe(0);
		});

		it('should return false on failure to delete on backend', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);

			await init(dom);

			const handler = getHandlerForAction(RESERVATIONS_DELETED);
			const result = await handler({
				ids: [
					'reservation-uuid-1',
					'reservation-uuid-2',
					'reservation-uuid-3',
				],
			});

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_DELETE_RESERVATIONS}`,
				{
					method: 'POST',
					body: JSON.stringify([
						'reservation-uuid-1',
						'reservation-uuid-2',
						'reservation-uuid-3',
					]),
				}
			);
			expect(result).toBe(false);
		});

		it('should return the number of deleted reservations on success', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true, data: { numberDeleted: 3 } }),
				{ status: 200 }
			);

			await init(dom);

			const handler = getHandlerForAction(RESERVATIONS_DELETED);
			const result = await handler({
				ids: [
					'reservation-uuid-1',
					'reservation-uuid-2',
					'reservation-uuid-3',
				],
			});

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_DELETE_RESERVATIONS}`,
				{
					method: 'POST',
					body: JSON.stringify([
						'reservation-uuid-1',
						'reservation-uuid-2',
						'reservation-uuid-3',
					]),
				}
			);
			expect(result).toBe(3);
		});
	});

	describe('handleSeatTypesUpdated', () => {
		it('should not send requests to the backend if data empty or not array', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					success: true,
					data: {
						updatedSeatTypes: 2,
						updatedTickets: 4,
						updatedPosts: 6,
					},
				}),
				{ status: 200 }
			);

			await init(dom);

			const handler = getHandlerForAction(SEAT_TYPES_UPDATED);
			const result = await handler([]);

			expect(fetch).not.toBeCalled();
			expect(result).toBe(false);
		});
	});

	it('should return false if backend returns error', async () => {
		const dom = getTestDocument();
		const iframe = dom.querySelector('.tec-tickets-seating__iframe');
		fetch.mockIf(
			/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
			JSON.stringify({ success: false }),
			{ status: 400 }
		);

		await init(dom);

		const handler = getHandlerForAction(SEAT_TYPES_UPDATED);
		const payload = [
			{
				id: 'some-seat-type-id',
				name: 'Some Seat Type',
				mapId: 'some-map-id',
				layoutId: 'some-layout-id',
				description: 'Some Seat Type description',
				seatsCount: 10,
			},
		];
		const result = await handler({ seatTypes: payload });

		expect(fetch).toBeCalledWith(
			`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_SEAT_TYPES_UPDATED}`,
			{
				method: 'POST',
				body: JSON.stringify(payload),
			}
		);
		expect(result).toBe(false);
	});

	it('should return the number of updated entities on success', async () => {
		const dom = getTestDocument();
		const iframe = dom.querySelector('.tec-tickets-seating__iframe');
		fetch.mockIf(
			/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
			JSON.stringify({
				success: true,
				data: {
					updatedSeatTypes: 2,
					updatedTickets: 4,
					updatedPosts: 6,
				},
			}),
			{ status: 200 }
		);

		await init(dom);

		const handler = getHandlerForAction(SEAT_TYPES_UPDATED);
		const payload = [
			{
				id: 'some-seat-type-1',
				name: 'Some Seat Type 1',
				mapId: 'some-map-id',
				layoutId: 'some-layout-id',
				description: 'Seat Type 1 description',
				seatsCount: 10,
			},
			{
				id: 'some-seat-type-2',
				name: 'Some Seat Type 2',
				mapId: 'some-map-id',
				layoutId: 'some-layout-id',
				description: 'Seat Type 2 description',
				seatsCount: 20,
			},
		];
		const result = await handler({ seatTypes: payload });

		expect(fetch).toBeCalledWith(
			`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_SEAT_TYPES_UPDATED}`,
			{
				method: 'POST',
				body: JSON.stringify(payload),
			}
		);
		expect(result).toMatchObject({
			updatedSeatTypes: 2,
			updatedPosts: 6,
			updatedTickets: 4,
		});
	});

	describe('handleReservationsUpdatedFollowingSeatTypes', () => {
		it('shoud not send requests to the backend if data empty', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					success: true,
					data: { updatedAttendees: 3 },
				}),
				{ status: 200 }
			);

			await init(dom);

			const handler = getHandlerForAction(
				RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES
			);
			const result = await handler({});

			expect(fetch).not.toBeCalled();
			expect(result).toBe(0);
		});

		it('should return false if backend returns error', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);

			await init(dom);

			const handler = getHandlerForAction(
				RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES
			);
			const payload = {
				'some-seat-type-1': [
					'some-reservation-uuid-1',
					'some-reservation-uuid-2',
					'some-reservation-uuid-3',
				],
				'some-seat-type-2': [
					'some-reservation-uuid-4',
					'some-reservation-uuid-5',
					'some-reservation-uuid-6',
				],
			};
			const result = await handler({ updated: payload });

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES}`,
				{
					method: 'POST',
					body: JSON.stringify(payload),
				}
			);
			expect(result).toBe(false);
		});

		it('should return the number of updated entities on success', async () => {
			const dom = getTestDocument();
			const iframe = dom.querySelector('.tec-tickets-seating__iframe');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					success: true,
					data: {
						updatedAttendees: 6,
					},
				}),
				{ status: 200 }
			);

			await init(dom);

			const handler = getHandlerForAction(
				RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES
			);
			const payload = {
				'some-seat-type-1': [
					'some-reservation-uuid-1',
					'some-reservation-uuid-2',
					'some-reservation-uuid-3',
				],
				'some-seat-type-2': [
					'some-reservation-uuid-4',
					'some-reservation-uuid-5',
					'some-reservation-uuid-6',
				],
			};
			const result = await handler({ updated: payload });

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES}`,
				{
					method: 'POST',
					body: JSON.stringify(payload),
				}
			);
			expect(result).toMatchObject({
				updatedAttendees: 6,
			});
		});
	});
});
