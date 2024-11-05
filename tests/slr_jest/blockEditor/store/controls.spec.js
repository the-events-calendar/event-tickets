import {
	controls,
	fetchSeatTypesByLayoutId,
} from '@tec/tickets/seating/blockEditor/store/controls';

require('jest-fetch-mock').enableMocks();

describe('controls', () => {
	beforeEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		fetch.resetMocks();
		fetch.enableMocks();
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('should call fetchSeatTypesByLayoutId with expected arguments', async () => {
		const action = {
			layoutId: 'test-1',
		};

		const mockResponse = {
			test: 1,
		};

		fetch.mockIf(
			/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
			JSON.stringify({ data: mockResponse })
		);

		const result = await controls.FETCH_SEAT_TYPES_FOR_LAYOUT(action);

		const expectedAction = 'tec_tickets_seating_get_seat_types_by_layout_id';

		expect(fetch).toBeCalledWith(
			`https://wordpress.test/wp-admin/admin-ajax.php?action=${expectedAction}&layout=test-1&_ajax_nonce=1234567890`,
			{
				headers: {
					Accept: 'application/json',
				},
				method: 'GET',
			}
		);

		expect(result).toMatchObject(mockResponse);
	});
});
