import { init } from '@tec/tickets/seating/admin/layoutEdit';
import { RESERVATIONS_DELETED } from '@tec/tickets/seating/service/api';
import { getHandlerForAction } from '@tec/tickets/seating/service/api';
import { ACTION_DELETE_RESERVATIONS } from '@tec/tickets/seating/ajax';
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
			const result = await handler({});

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
});
