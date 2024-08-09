import { init } from '@tec/tickets/seating/admin/layoutEdit';
import {
	getHandlerForAction,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	SEAT_TYPES_UPDATED,
} from '@tec/tickets/seating/service/api';
import { reset } from '@tec/tickets/seating/service/api/state';
import { getIframeElement } from '@tec/tickets/seating/service/iframe';

const iframeModule = require('@tec/tickets/seating/service/iframe');
const actionHandlersModule = require('@tec/tickets/seating/admin/action-handlers');

describe('Layouts Edit', () => {
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

	describe('init', () => {
		it('should initialize the iframe', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();

			await init(dom);

			expect(iframeModule.initServiceIframe).toHaveBeenCalledWith(iframe);
		});

		it('should register an action to handle reservations deleted', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();
			actionHandlersModule.handleReservationsDeleted = jest.fn();

			await init(dom);
			getHandlerForAction(RESERVATIONS_DELETED).call();

			expect(
				actionHandlersModule.handleReservationsDeleted
			).toHaveBeenCalled();
		});

		it('should register an action to handle seat types updated', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();
			actionHandlersModule.handleSeatTypesUpdated = jest.fn();

			await init(dom);
			getHandlerForAction(SEAT_TYPES_UPDATED).call();

			expect(
				actionHandlersModule.handleSeatTypesUpdated
			).toHaveBeenCalled();
		});

		it('should register an action to handle reservations updated', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();
			actionHandlersModule.handleReservationsUpdatedFollowingSeatTypes =
				jest.fn();

			await init(dom);
			getHandlerForAction(
				RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES
			).call();

			expect(
				actionHandlersModule.handleReservationsUpdatedFollowingSeatTypes
			).toHaveBeenCalled();
		});
	});
});
