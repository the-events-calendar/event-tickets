import {
	init,
	objectName,
	waitForModalElement,
	modalActionListener,
	closeModal,
	addLayoutByMapId,
	addNewLayout,
	handleSelectUpdates
} from '@tec/tickets/seating/admin/layouts/add-new-modal';
import {onReady} from '@tec/tickets/seating/utils';

require('jest-fetch-mock').enableMocks();
jest.mock( '@tec/tickets/seating/utils', () => {
	return {
		onReady: jest.fn(),
	}
})

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
		it('should import all properly', () => {
			expect( init ).toBeDefined();
			expect( objectName ).toBeDefined();
			expect( waitForModalElement ).toBeDefined();
			expect( modalActionListener ).toBeDefined();
			expect( closeModal ).toBeDefined();
			expect( addLayoutByMapId ).toBeDefined();
			expect( addNewLayout ).toBeDefined();
			expect( handleSelectUpdates ).toBeDefined();
		});

		it('should load the proper snapshot', () => {
			const dom = getTestDocument( 'layout-list' );
			expect( dom.body.innerHTML ).toMatchSnapshot();
		});

		it('should load proper extractor snapshot', () => {
			const dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			expect( dom.body.innerHTML ).toMatchSnapshot();
		});

		it('should have proper objectName', () => {
			expect(objectName).toMatchSnapshot();
		});
	});

	describe('select map options updated', () => {
		it('should match initial values', () => {
			const dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			document = dom;

			modalActionListener(dom);

			const selectMap = dom.getElementById( 'tec-tickets-seating__select-map' );
			const selectedOption = selectMap.options[selectMap.selectedIndex];

			console.log(selectedOption.innerHTML);

			const previewImg = dom.getElementById( 'tec-tickets-seating__new-layout-map-preview-img' );
			const infoElement = dom.querySelector( '.tec-tickets-seating__new-layout-map-info' );

			expect(previewImg).toMatchSnapshot();
			expect(infoElement).toMatchSnapshot();
		});

		it('should match select updated to map 3', () => {
			const dom = getTestDocument( 'layout-list', addNewLayoutModalExtractor );
			modalActionListener(dom);

			const selectMap = dom.getElementById( 'tec-tickets-seating__select-map' );
			selectMap.selectedIndex = 2;

			selectMap.dispatchEvent( new Event( 'change' ) );

			const previewImg = dom.getElementById( 'tec-tickets-seating__new-layout-map-preview-img' );
			const infoElement = dom.querySelector( '.tec-tickets-seating__new-layout-map-info' );

			expect(previewImg).toMatchSnapshot();
			expect(infoElement).toMatchSnapshot();
		});
	});
} );
