import {
	getString,
	registerDeleteAction,
	handleDelete,
	deleteListener,
	registerDestructiveEditAction,
	handleDestructiveEdit,
	destructiveEditActionListener,
	registerDuplicateLayoutAction,
	handleDuplicateAction,
	duplicateListener
} from '@tec/tickets/seating/admin/layouts/actions';

jest.mock( '@tec/tickets/seating/utils', () => ({
	redirectTo: jest.fn(),
	onReady: jest.fn(),
	getLocalizedString: ( slug, group ) => slug,
}));

import { redirectTo } from "@tec/tickets/seating/utils";

require('jest-fetch-mock').enableMocks();

const locationBackup = window.location;

function mockWindowLocation() {
	delete window.location;
	window.location = {
		reload: jest.fn(),
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

describe('layouts actions', () => {
	beforeEach(() => {
		fetch.resetMocks();
		// jest.resetModules();
		jest.resetAllMocks();
		mockWindowLocation();
	});

	afterEach(() => {
		fetch.resetMocks();
		// jest.resetModules();
		jest.resetAllMocks();
		window.location = locationBackup;
	});

	describe('delete action', () => {
		it('should register delete layout action', async () => {
			const dom = getTestDocument();
			const deleteButtons = dom.querySelectorAll('.delete-layout');

			// Mock the window.addEventListener function.
			deleteButtons.forEach((button) => {
				button.addEventListener = jest.fn();
			});

			registerDeleteAction(dom);

			deleteButtons.forEach((button) => {
				expect(button.addEventListener).toHaveBeenCalledWith('click', deleteListener);
			});
		});

		it('should handle delete request correctly', async () => {
			const dom = getTestDocument();
			const deleteButtons = dom.querySelectorAll('.delete-layout');
			global.confirm = jest.fn(() => true);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true })
			);

			// Click the first delete button.
			await handleDelete(deleteButtons[0]);

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
			await handleDelete(deleteButtons[1]);

			expect(confirm).toHaveBeenCalledWith(getString('delete-confirmation'));
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-2-uuid&mapId=map-1-uuid&action=tec_tickets_seating_service_delete_layout',
				{
					method: 'POST',
				}
			);
			expect(window.location.reload).toHaveBeenCalled();

			fetch.resetMocks();

			// Click the third delete button.
			await handleDelete(deleteButtons[2]);

			expect(confirm).toHaveBeenCalledWith(getString('delete-confirmation'));
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

			// Click the first delete button.
			await handleDelete(deleteButtons[0]);

			expect(confirm).not.toHaveBeenCalled();
			expect(fetch).not.toHaveBeenCalled();

			fetch.resetMocks();

			// Click the second delete button.
			await handleDelete(deleteButtons[1]);

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

			// Click the first delete button.
			await handleDelete(deleteButtons[0]);

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

			// Click the first delete button, the double await is needed to make sure we wait for the fetch to complete.
			await handleDelete(deleteButtons[0]);

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
			// jest.resetModules();
			jest.resetAllMocks();
		});

		afterEach(() => {
			fetch.resetMocks();
			// jest.resetModules();
			jest.resetAllMocks();
		});

		it('should register edit layout action', async () => {
			const dom = getTestDocument();
			const editButtons = dom.querySelectorAll('.edit-layout');

			// Mock the window.addEventListener function.
			editButtons.forEach((button) => {
				button.addEventListener = jest.fn();
			});

			registerDestructiveEditAction(dom);

			editButtons.forEach((button) => {
				expect(button.addEventListener).toHaveBeenCalledWith('click', destructiveEditActionListener);
			});
		});

		it('should handle edit request correctly', async () => {
			const dom = getTestDocument();
			const editButtons = dom.querySelectorAll('.edit-layout');
			global.confirm = jest.fn(() => true);

			// Click the first edit button.
			await handleDestructiveEdit(editButtons[0]);

			expect(confirm).toHaveBeenCalledWith(
				getString('edit-confirmation').replace('{count}', 1)
			);
			confirm.mockClear();

			// Click the second edit button.
			await handleDestructiveEdit(editButtons[1]);

			expect(confirm).toHaveBeenCalledWith(
				getString('edit-confirmation').replace('{count}', 3)
			);
			confirm.mockClear();

			// Click the third edit button, the layout has no events associated with it.
			await handleDestructiveEdit(editButtons[2]);

			expect(confirm).not.toHaveBeenCalled();
		});
	});

	describe('duplicate action', () => {
		it('should register duplicate layout action', async () => {
			const dom = getTestDocument();
			const duplicateButtons = dom.querySelectorAll('.duplicate-layout');

			// Mock the window.addEventListener function.
			duplicateButtons.forEach((button) => {
				button.addEventListener = jest.fn();
			});

			registerDuplicateLayoutAction(dom);

			duplicateButtons.forEach((button) => {
				expect(button.addEventListener).toHaveBeenCalledWith('click', duplicateListener);
			});
		});

		it('should handle duplicate request correctly', async () => {
			const dom = getTestDocument();
			const duplicateButtons = dom.querySelectorAll('.duplicate-layout');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true, data: 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' })
			);

			// Mock clicking the first duplicate button.
			await handleDuplicateAction(duplicateButtons[0]);

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).toHaveBeenCalled();

			// Mock clicking the second duplicate button.
			await handleDuplicateAction(duplicateButtons[1]);

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-2-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).toHaveBeenCalledWith( 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' );

			// Mock clicking the third duplicate button.
			await handleDuplicateAction(duplicateButtons[2]);

			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-3-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).toHaveBeenCalledWith('https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1');
		});

		it('should not duplicate or request on missing information', async () => {
			const dom = getTestDocument();
			// Delete the layout ID information from the first card.
			dom.querySelectorAll('.duplicate-layout')[0].dataset.layoutId = '';
			const duplicateButtons = dom.querySelectorAll('.duplicate-layout');
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: true, data: 'https://wordpress.test/wp-admin/layout-page/?layoutId=duplicated-layout-id-1' })
			);

			// Click the first duplicate button.
			await handleDuplicateAction(duplicateButtons[0]);

			expect(fetch).not.toHaveBeenCalled();
			expect(redirectTo).not.toHaveBeenCalled();
		});

		it('should fail on backend fail to duplicate layout', async () => {
			const dom = getTestDocument();
			const duplicateButtons = dom.querySelectorAll('.duplicate-layout');
			global.confirm = jest.fn(() => true);
			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);
			global.alert = jest.fn();

			// Click the first duplicate button.
			await handleDuplicateAction(duplicateButtons[0]);

			expect(alert).toHaveBeenCalledWith(getString('duplicate-failed'));
			expect(fetch).toBeCalledWith(
				'https://wordpress.test/wp-admin/admin-ajax.php?_ajax_nonce=1234567890&layoutId=layout-1-uuid&action=tec_tickets_seating_service_duplicate_layout',
				{
					method: 'POST',
				}
			);
			expect(redirectTo).not.toHaveBeenCalled();
			expect(alert).toHaveBeenCalledWith(getString('duplicate-failed'));
		});
	});
});
