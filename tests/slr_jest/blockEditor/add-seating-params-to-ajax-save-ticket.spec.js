import { dispatch, select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { filterSetBodyDetails } from '@tec/tickets/seating/blockEditor/add-seating-params-to-ajax-save-ticket';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
	dispatch: jest.fn(),
}));

describe('filterSetBodyDetails', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('should modify body', () => {
		const seatType = 'seat-type-uuid-1';
		const layoutId = 'layout-uuid-1';
		const eventCapacity = '40';
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		dispatch.mockReturnValue({
			setIsLayoutLocked: jest.fn(),
		});

		const body = new FormData();
		const modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('1');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(seatType);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);
	});

	it('should not modify body when layout not truthy', () => {
		const seatType = 'seat-type-uuid-1';
		let layoutId = false;
		const eventCapacity = '40';
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		dispatch.mockReturnValue({
			setIsLayoutLocked: jest.fn(),
		});

		const body = new FormData();
		let modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(null);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(null);

		layoutId = null;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
		});
		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(null);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(null);

		layoutId = '';
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
		});
		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(null);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(null);

		layoutId = 0;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
		});
		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(null);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(null);

		layoutId = undefined;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
		});
		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual(null);
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(null);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(null);
	});

	it('should not modify body when layout not truthy', () => {
		let seatType = false;
		const layoutId = 'layout-uuid-1';
		const eventCapacity = '40';
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		dispatch.mockReturnValue({
			setIsLayoutLocked: jest.fn(),
		});

		const body = new FormData();
		let modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('0');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual('');
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);

		seatType = null;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('0');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual('');
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);

		seatType = '';
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('0');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual('');
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);

		seatType = 0;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('0');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual('');
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);

		seatType = undefined;
		select.mockReturnValue({
			getCurrentLayoutId: () => layoutId,
			getTicketSeatType: () => seatType,
			getEventCapacity: () => eventCapacity,
		});

		modifiedBody = filterSetBodyDetails(body, 'client-id-1');
		expect(modifiedBody.get('ticket[seating][enabled]')).toEqual('0');
		expect(modifiedBody.get('ticket[seating][seatType]')).toEqual('');
		expect(modifiedBody.get('ticket[seating][layoutId]')).toEqual(layoutId);
		expect(modifiedBody.get('ticket[event_capacity]')).toEqual(eventCapacity);
	});
});
