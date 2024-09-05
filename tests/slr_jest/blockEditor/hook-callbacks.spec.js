import { dispatch, select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import SeatType from '@tec/tickets/seating/blockEditor/header/seat-type';
import { storeName } from '@tec/tickets/seating/blockEditor/store';
import {
	filterTicketIsAsc,
	filterHeaderDetails,
	filterSeatedTicketsAvailabilityMappedProps,
	filterSetBodyDetails,
	setSeatTypeForTicket,
} from '@tec/tickets/seating/blockEditor/hook-callbacks';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
	dispatch: jest.fn(),
}));

describe('setSeatTypeForTicket', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('should call dispatch with appropriate arguments', () => {
		const clientId = 'client-id-1';
		dispatch.mockReturnValue({
			setTicketSeatTypeByPostId: jest.fn(),
		});

		setSeatTypeForTicket(clientId);
		expect(dispatch).toHaveBeenCalledWith(storeName);
		expect(dispatch().setTicketSeatTypeByPostId).toHaveBeenCalledWith(
			clientId
		);
	});
});

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

describe('filterSeatedTicketsAvailabilityMappedProps', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('returns original mappedProps if hasSeats is false', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => false,
			isLayoutLocked: () => true,
		});
		const mappedProps = { foo: 'bar' };
		const newMappedProps = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('returns original mappedProps if layoutLocked is false', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => false,
		});
		const mappedProps = { foo: 'bar' };
		const newMappedProps = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('returns original mappedProps if layoutId is null', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => null,
		});
		const mappedProps = { foo: 'bar' };
		const newMappedProps = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('modifies total and available according to selected and total seats using Post ID having sold', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 10 },
			'general-admission': { name: 'General Admission', seats: 20 },
			ultra: { name: 'Ultra', seats: 5 },
		};
		const activeSeatTypesPostId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
		};
		const activeSeatTypesClientId = {
			99: 'vip',
			100: 'general-admission',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => activeSeatTypesPostId,
			getSeatTypesByClientID: () => activeSeatTypesClientId,
		});

		const mappedProps = {
			total: 100, // we have 65 blocked seats.
			available: 94, // Sold 6.
		};

		const result = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(result).toEqual({ total: 35, available: 24 });
	});

	it('modifies total and available according to selected and total seats using Client ID having sold', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 10 },
			'general-admission': { name: 'General Admission', seats: 20 },
			ultra: { name: 'Ultra', seats: 5 },
		};
		const activeSeatTypesPostId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
		};
		const activeSeatTypesClientId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
			103: 'ultra',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => activeSeatTypesPostId,
			getSeatTypesByClientID: () => activeSeatTypesClientId,
		});

		const mappedProps = {
			total: 100, // we have 65 blocked seats.
			available: 94, // Sold 6.
		};

		const result = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(result).toEqual({ total: 35, available: 29 });
	});

	it('modifies total and available according to selected and total seats using Client ID having none sold', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 10 },
			'general-admission': { name: 'General Admission', seats: 20 },
			ultra: { name: 'Ultra', seats: 5 },
		};
		const activeSeatTypesPostId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
		};
		const activeSeatTypesClientId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
			103: 'ultra',
			104: 'null',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => activeSeatTypesPostId,
			getSeatTypesByClientID: () => activeSeatTypesClientId,
		});

		const mappedProps = {
			total: 40, // we have 5 blocked seats.
			available: 40, // None sold..
		};

		const result = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(result).toEqual({ total: 35, available: 35 });
	});

	it('modifies total and available according to selected and total seats using Post ID having none sold', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 10 },
			'general-admission': { name: 'General Admission', seats: 20 },
			ultra: { name: 'Ultra', seats: 5 },
		};
		const activeSeatTypesPostId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
			102: 'vip',
			103: 'null',
		};
		const activeSeatTypesClientId = {
			99: 'vip',
			100: 'general-admission',
			101: 'general-admission',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => activeSeatTypesPostId,
			getSeatTypesByClientID: () => activeSeatTypesClientId,
		});

		const mappedProps = {
			total: 40, // we have 5 blocked seats.
			available: 40, // None sold..
		};

		const result = filterSeatedTicketsAvailabilityMappedProps(mappedProps);
		expect(result).toEqual({ total: 35, available: 30 });
	});
});

describe('filterHeaderDetails', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('add the new seat type to the array of details', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			getTicketSeatType: () => 'seat-type-uuid-1',
			getSeatTypesForLayout: () => ({
				'seat-type-uuid-1': {
					id: 'seat-type-uuid-1',
					name: 'Seat Type Name',
				},
			}),
			getCurrentLayoutId: () => 'layout-uuid-1',
		});
		const details = filterHeaderDetails([], 40);
		expect(details).toEqual([<SeatType name={'Seat Type Name'} />]);
	});

	it('dont add the new seat type to the array of details when not ASC', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => false,
			getTicketSeatType: () => 'seat-type-uuid-1',
			getSeatTypesForLayout: () => ({
				'seat-type-uuid-1': {
					id: 'seat-type-uuid-1',
					name: 'Seat Type Name',
				},
			}),
			getCurrentLayoutId: () => 'layout-uuid-1',
		});
		const details = filterHeaderDetails([], 40);
		expect(details).toEqual([]);
	});

	it('dont add the new seat type to the array of details when unknown type', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			getTicketSeatType: () => 'seat-type-uuid-2',
			getSeatTypesForLayout: () => ({
				'seat-type-uuid-1': {
					id: 'seat-type-uuid-1',
					name: 'Seat Type Name',
				},
			}),
			getCurrentLayoutId: () => 'layout-uuid-1',
		});
		const details = filterHeaderDetails([], 40);
		expect(details).toEqual([]);
	});

	it('dont add the new seat type to the array of details when unknown type', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			getTicketSeatType: () => 'seat-type-uuid-1',
			getSeatTypesForLayout: () => ({
				'seat-type-uuid-2': {
					id: 'seat-type-uuid-2',
					name: 'Seat Type Name',
				},
			}),
			getCurrentLayoutId: () => 'layout-uuid-1',
		});
		const details = filterHeaderDetails([], 40);
		expect(details).toEqual([]);
	});
});

describe('filterTicketIsAsc', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	it('returns true when ticket is ASC', () => {
		select.mockReturnValue({
			getTicketSeatType: () => 'seat-type-uuid-1',
		});
		const newMappedProps = filterTicketIsAsc(false, 40);
		expect(newMappedProps).toEqual(true);
	});

	it('returns original when ticket is not ASC', () => {
		select.mockReturnValue({
			getTicketSeatType: () => null,
		});
		const newMappedPropsFromFalse = filterTicketIsAsc(false, 40);
		expect(newMappedPropsFromFalse).toEqual(false);

		const newMappedPropsFromTrue = filterTicketIsAsc(true, 40);
		expect(newMappedPropsFromTrue).toEqual(true);
	});
});
