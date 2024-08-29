import { init } from '@tec/tickets/seating/admin/layoutEdit';
import { reset } from '@tec/tickets/seating/service/api/state';
import { getIframeElement } from '@tec/tickets/seating/service/iframe';

const iframeModule = require('@tec/tickets/seating/service/iframe');

jest.mock( '@tec/tickets/seating/utils', () => ({
	redirectTo: jest.fn(),
	onReady: jest.fn(),
}));

describe('Maps Edit', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
		reset();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
		reset();
	});

	describe('init', () => {
		it('should initialize the iframe', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();

			await init(dom);

			expect(iframeModule.initServiceIframe).toHaveBeenCalledWith(iframe);
		});
	});
});
