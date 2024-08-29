import {
	getString,
	registerDeleteAction
} from '@tec/tickets/seating/admin/maps/actions';

require('jest-fetch-mock').enableMocks();

const locationBackup = window.location;

function mockWindowLocation() {
	delete window.location;
	window.location = {
		reload: jest.fn(),
	};
}

describe('map actions', () => {
	beforeEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
		mockWindowLocation();
	});

	afterEach(() => {
		fetch.resetMocks();
		jest.resetModules();
		jest.resetAllMocks();
		window.location = locationBackup;
	});

	describe('delete action', () => {
		it('should handle delete request correctly', async () => {
			const dom = getTestDocument( 'maps-list' );
			const deleteButtons = dom.querySelectorAll('.delete-map');
			global.confirm = jest.fn(() => true);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			registerDeleteAction(dom);

			// Click the first delete button, the double await is needed to make sure we wait for the fetch to complete.
			await await deleteButtons[0].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('delete-confirmation')
			);
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&mapId=map-uuid-1&action=tec_tickets_seating_service_delete_map',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).toHaveBeenCalled();

			fetch.resetMocks();

			// Click the second delete button.
			await await deleteButtons[1].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('delete-confirmation')
			);
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&mapId=map-uuid-2&action=tec_tickets_seating_service_delete_map',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).toHaveBeenCalled();

			fetch.resetMocks();

			// Click the third delete button.
			await await deleteButtons[2].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('delete-confirmation')
			);
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&mapId=map-uuid-3&action=tec_tickets_seating_service_delete_map',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).toHaveBeenCalled();
		});

		it('should not delete on backend if not confirmed', async () => {
			const dom = getTestDocument( 'maps-list' );
			const deleteButtons = dom.querySelectorAll('.delete-map');
			// Do not confirm the delete request.
			global.confirm = jest.fn(() => false);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			registerDeleteAction(dom);

			// Click the first delete button.
			await await deleteButtons[0].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('delete-confirmation')
			);
			expect(fetch).not.toHaveBeenCalled();
		});

		it('should fail on backend fail to delete layout', async () => {
			const dom = getTestDocument( 'maps-list' );
			const deleteButtons = dom.querySelectorAll('.delete-map');
			global.confirm = jest.fn(() => true);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);
			global.alert = jest.fn();

			registerDeleteAction(dom);

			// Click the first delete button, the double await is needed to make sure we wait for the fetch to complete.
			await await deleteButtons[0].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('delete-confirmation')
			);
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&mapId=map-uuid-1&action=tec_tickets_seating_service_delete_map',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).not.toHaveBeenCalled();
			expect(alert).toHaveBeenCalledWith(getString('delete-failed'));
		});
	});
});
