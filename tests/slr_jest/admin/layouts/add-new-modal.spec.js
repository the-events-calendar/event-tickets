import {
	init,
	addLayoutModal,
	waitForModalElement,
	modalActionListener,
	closeModal,
	addLayoutByMapId,
	addNewLayout,
	handleSelectUpdates
} from '@tec/tickets/seating/admin/layouts/add-new-modal';
import {ACTION_ADD_NEW_LAYOUT, ajaxNonce, ajaxUrl} from '@tec/tickets/seating/ajax';
import {redirectTo, getLocalizedString} from "@tec/tickets/seating/utils";

require('jest-fetch-mock').enableMocks();
jest.mock( '@tec/tickets/seating/utils', () => ({
	redirectTo: jest.fn(),
	onReady: jest.fn(),
	getLocalizedString: jest.fn(),
}));

/**
 * Extracts and appends the modal dialog to the document as a click of the button would do.
 *
 * @param {string} html The source HTML of the whole document.
 *
 * @return {Document} The document element, transformed to include the modal dialog.
 */
function addNewLayoutModalExtractor(html) {
	const wholeDocument = new DOMParser().parseFromString(html, 'text/html');
	const modalHtml = wholeDocument
		.querySelector(
			'[data-js="dialog-content-tec-tickets-seating-layouts-modal"]'
		)
		.innerHTML;
	wholeDocument
		.querySelector('.tec-tickets__seating-tab-wrapper')
		.insertAdjacentHTML('afterend', modalHtml);

	return wholeDocument;
}

describe( 'add-new-modal', () => {
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

	describe('should match the snapshot', () => {
		it('should load the proper snapshot', () => {
			const dom = getTestDocument( 'layout-list' );
			expect( dom.body.innerHTML ).toMatchSnapshot();
		});

		it('should load proper extractor snapshot', () => {
			const dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			expect( dom.body.innerHTML ).toMatchSnapshot();
		});

		it('should have proper objectName', () => {
			expect(addLayoutModal).toMatchSnapshot();
		});
	});

	describe('select map options updated', () => {
		it('should match initial values', async () => {
			let dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			document.body.innerHTML = dom.body.innerHTML;
			modalActionListener();

			const previewImg = document.getElementById('tec-tickets-seating__new-layout-map-preview-img');
			const infoElement = document.querySelector('.tec-tickets-seating__new-layout-map-info');

			expect(previewImg).toMatchSnapshot();
			expect(infoElement).toMatchSnapshot();
		});

		it('should match select updated to map 3', () => {
			let dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			document.body.innerHTML = dom.body.innerHTML;
			modalActionListener();

			const selectMap = document.getElementById( 'tec-tickets-seating__select-map' );
			selectMap.selectedIndex = 2;

			selectMap.dispatchEvent( new Event( 'change' ) );

			const previewImg = document.getElementById( 'tec-tickets-seating__new-layout-map-preview-img' );
			const infoElement = document.querySelector( '.tec-tickets-seating__new-layout-map-info' );

			expect(previewImg).toMatchSnapshot();
			expect(infoElement).toMatchSnapshot();
		});
	});

	describe('cancel button clicked', () => {
		beforeEach(() => {
			window = {};
			// Mock the modal object on the window
			window[addLayoutModal] = {
				_hide: jest.fn(),
			};
		});

		afterEach(() => {
			// Clean up the mock
			delete window[addLayoutModal];
		});

		it('should trigger closeModal', () => {
			let dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			document.body.innerHTML = dom.body.innerHTML;
			modalActionListener();

			const cancelButton = document.querySelector( '.tec-tickets-seating__new-layout-button-cancel' );
			cancelButton.click();

			expect(window[addLayoutModal]._hide).toHaveBeenCalledTimes(1);
		});
	});

	describe( 'add new layout button clicked', () => {
		beforeEach(() => {
			fetch.resetMocks();
			jest.resetModules();
			jest.resetAllMocks();
			const dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			document.body.innerHTML = dom.body.innerHTML;
			modalActionListener();
		});

		afterEach(() => {
			fetch.resetMocks();
			jest.resetModules();
			jest.resetAllMocks();
			document.body.innerHTML = '';
		});

		it('should send proper fetch request and update visibility', async () => {
			const addButton = document.querySelector('.tec-tickets-seating__new-layout-button-add');
			const wrapper = document.querySelector('.tec-tickets-seating__new-layout-wrapper');
			const mapSelect = document.getElementById('tec-tickets-seating__select-map');
			const mapId = mapSelect.selectedOptions[0].value;

			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({
					data : ajaxUrl,
				}),
			);

			await addButton.click();

			expect( addButton.disabled ).toBe( true );
			expect( wrapper.style.opacity ).toBe( '0.5' );

			const url = new URL(ajaxUrl);
			url.searchParams.set('_ajax_nonce', ajaxNonce);
			url.searchParams.set('mapId', mapId);
			url.searchParams.set('action', ACTION_ADD_NEW_LAYOUT);
			expect(fetch).toBeCalledWith(
				url.toString(),
				{
					method: 'POST',
				}
			);

			// Let async operations complete.
			await new Promise(resolve => setTimeout(resolve, 0));

			expect(redirectTo).toBeCalledWith(ajaxUrl);
		});

		it('should reset visibility on failed request', async () => {
			const addButton = document.querySelector('.tec-tickets-seating__new-layout-button-add');
			const wrapper = document.querySelector('.tec-tickets-seating__new-layout-wrapper');

			fetch.mockIf(
				/^https:\/\/wordpress\.test\/wp-admin\/admin-ajax\.php?.*$/,
				JSON.stringify({ success: false }),
				{ status: 400 }
			);

			global.alert = jest.fn();

			await addButton.click();

			// Let async operations complete.
			await new Promise(resolve => setTimeout(resolve, 0));

			expect( global.alert ).toHaveBeenCalled();
			expect( global.alert ).toHaveBeenCalledWith(getLocalizedString('add-failed', 'layouts'));
			expect( addButton.disabled ).toBe( false );
			expect( wrapper.style.opacity ).toBe( '1' );
		});
	});

	describe( 'init', () => {
		it( 'should show the modal', async () => {
			global.window[addLayoutModal] = {
				on: jest.fn(),
			}
			await init();
			expect( window[addLayoutModal].on ).toHaveBeenCalledWith( 'show', expect.any( Function ) );
		} );
	})
} );
