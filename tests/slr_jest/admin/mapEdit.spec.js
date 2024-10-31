import { init } from '@tec/tickets/seating/admin/layoutEdit';
import { reset } from '@tec/tickets/seating/service/api/state';
import { getIframeElement } from '@tec/tickets/seating/service/iframe';
import {
	getHandlerForAction,
	INBOUND_SET_ELEMENT_HEIGHT,
} from '@tec/tickets/seating/service/api';

const iframeModule = require('@tec/tickets/seating/service/iframe');
const actionHandlersModule = require('@tec/tickets/seating/admin/action-handlers');

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
			const dom = getTestDocument('map-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();

			await init(dom);

			expect(iframeModule.initServiceIframe).toHaveBeenCalledWith(iframe);
		});
	});

	describe('handleResize', () => {
		it('should be registered and resize the iframe', async () => {
			const dom = getTestDocument('map-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);

			iframeModule.initServiceIframe = jest.fn();
			actionHandlersModule.handleReservationsDeleted = jest.fn();

			await init(dom);

			// Get the registered resize handler.
			const resizeHandler = getHandlerForAction(INBOUND_SET_ELEMENT_HEIGHT);

			resizeHandler({height: 100}, dom);
			expect(iframe.style.height).toBe( 100 + 'px' );

			resizeHandler({height: 200}, dom);
			expect(iframe.style.height).toBe( 200 + 'px' );
		});
	})
});
