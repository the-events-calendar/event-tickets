import { init, goToAssociatedEvents } from '@tec/tickets/seating/admin/layoutEdit';
import {
	handleReservationsDeleted,
	handleReservationsUpdatedFollowingSeatTypes,
	handleSeatTypesUpdated,
} from '@tec/tickets/seating/admin/action-handlers';
import {
	getAssociatedEventsUrl,
	getHandlerForAction,
	RESERVATIONS_DELETED,
	RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES,
	SEAT_TYPES_UPDATED,
	GO_TO_ASSOCIATED_EVENTS,
} from '@tec/tickets/seating/service/api';
import { reset } from '@tec/tickets/seating/service/api/state';
import { getIframeElement } from '@tec/tickets/seating/service/iframe';
import {redirectTo} from "@tec/tickets/seating/utils";

const iframeModule = require('@tec/tickets/seating/service/iframe');
const actionHandlersModule = require('@tec/tickets/seating/admin/action-handlers');

jest.mock( '@tec/tickets/seating/utils', () => ({
	redirectTo: jest.fn(),
	onReady: jest.fn(),
}));

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

	describe('init', () => {
		it('should initialize the iframe', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();

			await init(dom);

			expect(iframeModule.initServiceIframe).toHaveBeenCalledWith(iframe);
		});

		it('should register actions', async () => {
			const dom = getTestDocument('layout-edit');
			const iframe = getIframeElement(dom);
			expect(iframe).toBeInstanceOf(HTMLIFrameElement);
			iframeModule.initServiceIframe = jest.fn();
			actionHandlersModule.handleReservationsDeleted = jest.fn();

			await init(dom);
			expect(getHandlerForAction(RESERVATIONS_DELETED)).toBe(
				handleReservationsDeleted
			);
			expect(getHandlerForAction(SEAT_TYPES_UPDATED)).toBe(
				handleSeatTypesUpdated
			);
			expect(
				getHandlerForAction(RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES)
			).toBe(handleReservationsUpdatedFollowingSeatTypes);

			expect(getHandlerForAction(GO_TO_ASSOCIATED_EVENTS)).toBe(
				goToAssociatedEvents
			)
		});
	});

	describe('goToAssociatedEvents', () => {
		it('should redirect with valid layoutID data', () => {
			const data = {
				layoutId: 'some-layout-id',
			}

			goToAssociatedEvents(data);
			expect(redirectTo).toBeCalledWith(getAssociatedEventsUrl(data.layoutId));
		});

		it('should not redirect with invalid layoutID data', () => {
			const data = {
				noLayoutId: '',
			}

			goToAssociatedEvents(data);
			expect(redirectTo).not.toBeCalled();
		});
	})
});
