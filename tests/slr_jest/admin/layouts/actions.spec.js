import {
	getString,
	registerDeleteAction,
	registerDestructiveEditAction,
	registerDuplicateLayoutAction,
} from '@tec/tickets/seating/admin/layouts/actions';

import { redirectTo } from "@tec/tickets/seating/utils";

require('jest-fetch-mock').enableMocks();

const locationBackup = window.location;

function mockWindowLocation() {
	delete window.location;
	window.location = {
		reload: jest.fn(),
		href: '',
	};
}

function getTestDocument() {
	return new DOMParser().parseFromString(
		`<div class="event-tickets"">
					<div class="tec-tickets__seating-tab__card">
						<button
							class="delete-layout"
							data-layout-id="layout-1-uuid"
							data-map-id="map-1-uuid"
							data-event-count="1"
						>
							Delete
						</button>
						<button
							class="edit-layout"
							data-layout-id="layout-1-uuid"
							data-map-id="map-1-uuid"
							data-event-count="1"
						>
							Edit
						</button>
						<button
							class="duplicate-layout"
							data-layout-id="layout-1-uuid"
						>
							Duplicate
						</button>
					</div>

					<div class="tec-tickets__seating-tab__card">
						<button
							class="delete-layout"
							data-layout-id="layout-2-uuid"
							data-map-id="map-1-uuid"
							data-event-count="3"
						>
							Delete
						</button>
						<button
							class="edit-layout"
							data-layout-id="layout-2-uuid"
							data-map-id="map-1-uuid"
							data-event-count="3"
						>
							Edit
						</button>
						<button
							class="duplicate-layout"
							data-layout-id="layout-2-uuid"
						>
							Duplicate
						</button>
					</div>

					<div class="tec-tickets__seating-tab__card">
						<button
							class="delete-layout"
							data-layout-id="layout-3-uuid"
							data-map-id="map-2-uuid"
							data-event-count="0"
						>
							Delete
						</button>
						<button
							class="edit-layout"
							data-layout-id="layout-3-uuid"
							data-map-id="map-2-uuid"
							data-event-count="0"
						>
							Edit
						</button>
						<button
							class="duplicate-layout"
							data-layout-id="layout-3-uuid"
						>
							Duplicate
						</button>
					</div>
				</div>`,
		'text/html'
	);
}

jest.mock( '@tec/tickets/seating/utils', () => ({
	redirectTo: jest.fn(),
	onReady: jest.fn(),
	// getLocalizedString: () => 'string',
	getLocalizedString: jest.fn(),
}))

describe('layouts actions', () => {
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
			const dom = getTestDocument();
			const deleteButtons = dom.querySelectorAll('.delete-layout');
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
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&mapId=map-1-uuid&action=tec_tickets_seating_service_delete_layout',
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
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-2-uuid&mapId=map-1-uuid&action=tec_tickets_seating_service_delete_layout',
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
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-3-uuid&mapId=map-2-uuid&action=tec_tickets_seating_service_delete_layout',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).toHaveBeenCalled();
		});

		it('should not issue delete confirmation or request on missing information', async () => {
			const dom = getTestDocument();
			// Delete the layout ID information from the first delete card.
			dom.querySelectorAll('.delete-layout')[0].dataset.layoutId = '';
			// Delete the map ID information from the second delete card.
			dom.querySelectorAll('.delete-layout')[1].dataset.mapId = '';
			const deleteButtons = dom.querySelectorAll('.delete-layout');
			global.confirm = jest.fn(() => true);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			registerDeleteAction(dom);

			// Click the first delete button.
			await await deleteButtons[0].click();

			expect(confirm).not.toHaveBeenCalled();
			expect(fetch).not.toHaveBeenCalled();

			fetch.resetMocks();

			// Click the second delete button.
			await await deleteButtons[1].click();

			expect(confirm).not.toHaveBeenCalled();
			expect(fetch).not.toHaveBeenCalled();
		});

		it('should not delete on backend if not confirmed', async () => {
			const dom = getTestDocument();
			const deleteButtons = dom.querySelectorAll('.delete-layout');
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
			const dom = getTestDocument();
			const deleteButtons = dom.querySelectorAll('.delete-layout');
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
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&mapId=map-1-uuid&action=tec_tickets_seating_service_delete_layout',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).not.toHaveBeenCalled();
			expect(alert).toHaveBeenCalledWith(getString('delete-failed'));
		});
	});

	describe('edit action', () => {
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

		it('should handle edit request correctly', async () => {
			const dom = getTestDocument();
			const editButtons = dom.querySelectorAll('.edit-layout');
			global.confirm = jest.fn(() => true);

			registerDestructiveEditAction(dom);

			// Click the first delete buttgon, the double await is needed to make sure we wait for the fetch to complete.
			await await editButtons[0].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('edit-confirmation').replace('{count}', 1)
			);
			confirm.mockClear();

			// Click the second delete button.
			await await editButtons[1].click();

			expect(confirm).toHaveBeenCalledWith(
				getString('edit-confirmation').replace('{count}', 3)
			);
			confirm.mockClear();

			// Click the third delete button, the layout has no events associated with it.
			await await editButtons[2].click();

			expect(confirm).not.toHaveBeenCalled();
		});
	});

	describe('duplicate action', () => {
		it('should handle duplicate request correctly', async () => {
			const dom = getTestDocument();
			const duplicateButtons = dom.querySelectorAll('.duplicate-layout');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true, data: 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' })
			);

			registerDuplicateLayoutAction(dom);

			// Click the first duplicate button, the double await is needed to make sure we wait for the fetch to complete.
			await await duplicateButtons[0].click();

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			// expect(window.location.href).toBe('https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1');
			expect(redirectTo).toHaveBeenCalledWith( 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' );

			fetch.resetMocks();

			// Click the second duplicate button.
			await await duplicateButtons[1].click();

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-2-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).toHaveBeenCalledWith( 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' );

			fetch.resetMocks();

			// Click the third duplicate button.
			await await duplicateButtons[2].click();

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-3-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).toHaveBeenCalledWith('https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1');
		});

		// it('should not issue delete confirmation or request on missing information', async () => {
		// 	const dom = getTestDocument();
		// 	// Delete the layout ID information from the first delete card.
		// 	dom.querySelectorAll('.delete-layout')[0].dataset.layoutId = '';
		// 	// Delete the map ID information from the second delete card.
		// 	dom.querySelectorAll('.delete-layout')[1].dataset.mapId = '';
		// 	const deleteButtons = dom.querySelectorAll('.delete-layout');
		// 	global.confirm = jest.fn(() => true);
		// 	fetch.mockIf(
		// 		/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
		// 		JSON.stringify({ success: true })
		// 	);

		// 	registerDuplicateLayoutAction(dom);

		// 	// Click the first delete button.
		// 	await await deleteButtons[0].click();

		// 	expect(confirm).not.toHaveBeenCalled();
		// 	expect(fetch).not.toHaveBeenCalled();

		// 	fetch.resetMocks();

		// 	// Click the second delete button.
		// 	await await deleteButtons[1].click();

		// 	expect(confirm).not.toHaveBeenCalled();
		// 	expect(fetch).not.toHaveBeenCalled();
		// });

		// it('should not delete on backend if not confirmed', async () => {
		// 	const dom = getTestDocument();
		// 	const deleteButtons = dom.querySelectorAll('.delete-layout');
		// 	// Do not confirm the delete request.
		// 	global.confirm = jest.fn(() => false);
		// 	fetch.mockIf(
		// 		/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
		// 		JSON.stringify({ success: true })
		// 	);

		// 	registerDuplicateLayoutAction(dom);

		// 	// Click the first delete button.
		// 	await await deleteButtons[0].click();

		// 	expect(confirm).toHaveBeenCalledWith(
		// 		getString('delete-confirmation')
		// 	);
		// 	expect(fetch).not.toHaveBeenCalled();
		// });

		// it('should fail on backend fail to delete layout', async () => {
		// 	const dom = getTestDocument();
		// 	const deleteButtons = dom.querySelectorAll('.delete-layout');
		// 	global.confirm = jest.fn(() => true);
		// 	fetch.mockIf(
		// 		/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
		// 		JSON.stringify({ success: false }),
		// 		{ status: 400 }
		// 	);
		// 	global.alert = jest.fn();

		// 	registerDuplicateLayoutAction(dom);

		// 	// Click the first delete button, the double await is needed to make sure we wait for the fetch to complete.
		// 	await await deleteButtons[0].click();

		// 	expect(confirm).toHaveBeenCalledWith(
		// 		getString('delete-confirmation')
		// 	);
		// 	expect(fetch).toBeCalledWith(
		// 		'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&mapId=map-1-uuid&action=tec_tickets_seating_service_delete_layout',
		// 		{
		// 			method: 'POST',
		// 		}
		// 	);
		// 	expect(window.location.reload).not.toHaveBeenCalled();
		// 	expect(alert).toHaveBeenCalledWith(getString('delete-failed'));
		// });
	});
});
