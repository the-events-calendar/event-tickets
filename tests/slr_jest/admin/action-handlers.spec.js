import {
	ACTION_DELETE_RESERVATIONS,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_SEAT_TYPE_DELETED,
} from '@tec/tickets/seating/ajax';
import {
	handleReservationsDeleted,
	handleReservationsUpdatedFollowingSeatTypes,
	handleSeatTypesUpdated,
	handleSeatTypeDeleted,
} from '@tec/tickets/seating/admin/action-handlers';

require('jest-fetch-mock').enableMocks();

describe('action handlers', () => {
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

	describe('handeReservationsDeleted', () => {
		it('should not send requests to the backed on missing ids key', async () => {
			const result = await handleReservationsDeleted({});

			expect(fetch).not.toBeCalled();
			expect(result).toBe(0);
		});

		it('should not send requests to the backed on empty reservation UUIds', async () => {
			const result = await handleReservationsDeleted({ ids: [] });

			expect(fetch).not.toBeCalled();
			expect(result).toBe(0);
		});

		it('should return false on failure to delete on backend', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);

			const result = await handleReservationsDeleted({
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
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true, data: { numberDeleted: 3 } }),
				{ status: 200 }
			);

			const result = await handleReservationsDeleted({
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

			const result = await handleSeatTypesUpdated({});

			expect(fetch).not.toBeCalled();
			expect(result).toBe(false);
		});

		it('should return false if backend returns error', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);

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
			const result = await handleSeatTypesUpdated({ seatTypes: payload });

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
			const result = await handleSeatTypesUpdated({ seatTypes: payload });

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
	});

	describe('handleReservationsUpdatedFollowingSeatTypes', () => {
		it('shoud not send requests to the backend if data empty', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					success: true,
					data: { updatedAttendees: 3 },
				}),
				{ status: 200 }
			);

			const result = await handleReservationsUpdatedFollowingSeatTypes(
				{}
			);

			expect(fetch).not.toBeCalled();
			expect(result).toBe(0);
		});

		it('should return false if backend returns error', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
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
			const result = await handleReservationsUpdatedFollowingSeatTypes({
				updated: payload,
			});

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
			const result = await handleReservationsUpdatedFollowingSeatTypes({
				updated: payload,
			});

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

	describe('handleSeatTypeDeleted', () => {
		it('should not send fetch request if data empty', async () => {
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true,
					data: { seatTypesUpdated: true } }),
				{ status: 200 }
			);

			const result = await handleSeatTypeDeleted({});
			expect(fetch).not.toBeCalled();
			expect(result).toBe(false);
		});

		it('should send proper fetch request', () => {
			const payload = {
				"deletedId": 'some-seat-type-2',
				"transferTo": {
					id: 'some-seat-type-1',
					name: 'Some Seat Type 1',
					mapId: 'some-map-id',
					layoutId: 'some-layout-id',
					description: 'Seat Type 1 description',
					seatsCount: 50,
				}
			};

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

			const result = handleSeatTypeDeleted( payload );

			expect(fetch).toBeCalledWith(
				`https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&action=${ACTION_SEAT_TYPE_DELETED}`,
				{
					method: 'POST',
					body: JSON.stringify(payload),
				}
			);
		});
	});
});
