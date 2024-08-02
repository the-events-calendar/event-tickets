import {
	getEstablishingReadiness,
	getHandlerForAction,
	getIsReady,
	getToken,
	registerAction,
	removeAction,
	setEstablishingReadiness,
	setIsReady,
	setToken,
	reset,
} from '@tec/tickets/seating/service/api/state';
import {
	GO_TO_LAYOUTS_HOME,
	GO_TO_MAPS_HOME,
	INBOUND_APP_READY,
	INBOUND_APP_READY_FOR_DATA,
	INBOUND_SEATS_SELECTED,
	LAYOUT_CREATED_UPDATED,
	MAP_CREATED_UPDATED,
	OUTBOUND_HOST_READY,
	OUTBOUND_SEAT_TYPE_TICKETS,
	SEAT_TYPE_CREATED_UPDATED,
} from '@tec/tickets/seating/service/api/service-actions';
import {
	defaultMessageHandler,
	onGoToLayoutsHome,
	onGoToMapsHome,
	onLayoutCreatedUpdated,
	onMapCreatedUpdated,
	onSeatTypeCreatedUpdated,
} from '@tec/tickets/seating/service/api/message-handlers';

describe('State', () => {
	beforeEach(() => {
		reset();
	});

	afterAll(() => {
		reset();
	});

	it('should register some actions by default', () => {
		expect(getHandlerForAction('default')).toBe(defaultMessageHandler);
		expect(getHandlerForAction(INBOUND_APP_READY)).toBe(
			defaultMessageHandler
		);
		expect(getHandlerForAction(INBOUND_APP_READY_FOR_DATA)).toBe(
			defaultMessageHandler
		);
		expect(getHandlerForAction(INBOUND_SEATS_SELECTED)).toBe(
			defaultMessageHandler
		);
		expect(getHandlerForAction(OUTBOUND_HOST_READY)).toBe(
			defaultMessageHandler
		);
		expect(getHandlerForAction(OUTBOUND_SEAT_TYPE_TICKETS)).toBe(
			defaultMessageHandler
		);
		expect(getHandlerForAction(MAP_CREATED_UPDATED)).toBe(
			onMapCreatedUpdated
		);
		expect(getHandlerForAction(LAYOUT_CREATED_UPDATED)).toBe(
			onLayoutCreatedUpdated
		);
		expect(getHandlerForAction(SEAT_TYPE_CREATED_UPDATED)).toBe(
			onSeatTypeCreatedUpdated
		);
		expect(getHandlerForAction(GO_TO_MAPS_HOME)).toBe(onGoToMapsHome);
		expect(getHandlerForAction(GO_TO_LAYOUTS_HOME)).toBe(onGoToLayoutsHome);
	});

	it('should return the default handler for an action that is not registered', () => {
		expect(getHandlerForAction('unknown')).toBe(defaultMessageHandler);
	});

	it('should allow registering and deregistering an action', () => {
		const callback = () => {};
		registerAction('test-action', callback);

		expect(getHandlerForAction('test-action')).toBe(callback);

		removeAction('test-action');

		expect(getHandlerForAction('test-action')).toBe(defaultMessageHandler);
	});

	it('should allow controlling ready and establishing readiness state', () => {
		expect(getIsReady()).toBe(false);
		expect(getEstablishingReadiness()).toBe(false);

		setIsReady(true);
		setEstablishingReadiness(true);

		expect(getIsReady()).toBe(true);
		expect(getEstablishingReadiness()).toBe(true);
	});

	it('should allow setting and unsetting the ephemeral token', () => {
		expect(getToken()).toBe(null);

		setToken('test-token');

		expect(getToken()).toBe('test-token');

		setToken(null);

		expect(getToken()).toBe(null);
	});
});
