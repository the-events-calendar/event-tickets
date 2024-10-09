import { select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { filterCapacityTableMappedProps } from '@tec/tickets/seating/blockEditor/capacity-table';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
}));

describe('filterCapacityTableMappedProps', () => {
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
		const newMappedProps = filterCapacityTableMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('returns original mappedProps if layoutLocked is false', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => false,
		});
		const mappedProps = { foo: 'bar' };
		const newMappedProps = filterCapacityTableMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('returns original mappedProps if layoutId is null', () => {
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => null,
		});
		const mappedProps = { foo: 'bar' };
		const newMappedProps = filterCapacityTableMappedProps(mappedProps);
		expect(newMappedProps).toEqual(mappedProps);
	});

	it('adds rowsAfter and updates totalCapacity if hasSeats and layoutLocked are true', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 20 },
			'general-admission': { name: 'General Admission', seats: 20 },
		};
		const activeSeatTypes = {
			99: 'vip',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => activeSeatTypes,
		});

		const mappedProps = {
			rowsAfter: [],
			independentCapacity: 20,
			totalCapacity: 40,
			sharedCapacity: 20,
			sharedTicketItems: 'items',
		};

		const result = filterCapacityTableMappedProps(mappedProps);
		expect(result).toMatchSnapshot();
	});

	it('adds rowsAfter and updates totalCapacity if has multiple seat types', () => {
		const seatTypes = {
			vip: { name: 'VIP', seats: 20 },
			'general-admission': { name: 'General Admission', seats: 20 },
		};
		const seatTypesByPostId = {
			99: 'vip',
			100: 'general-admission',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypes,
			getSeatTypesByPostID: () => seatTypesByPostId,
		});

		const mappedProps = {
			rowsAfter: [],
			independentCapacity: 20,
			totalCapacity: 60,
			sharedCapacity: 40,
			sharedTicketItems: 'items',
		};

		const result = filterCapacityTableMappedProps(mappedProps);
		expect(result).toMatchSnapshot();
	});

	it('adds rowsAfter and updates totalCapacity if has multiple seat types and multipe of the same type', () => {
		const seatTypesForLayout = {
			vip: { name: 'VIP', seats: 20 },
			'general-admission': { name: 'General Admission', seats: 20 },
			'ultra-vip': { name: 'Ultra VIP', seats: 40 },
		};
		const seatTypesByPostId = {
			99: 'vip',
			100: 'vip',
			101: 'general-admission',
			102: 'general-admission',
			103: 'ultra-vip',
		};
		select.mockReturnValue({
			isUsingAssignedSeating: () => true,
			isLayoutLocked: () => true,
			getCurrentLayoutId: () => 'layout-id',
			getSeatTypesForLayout: () => seatTypesForLayout,
			getSeatTypesByPostID: () => seatTypesByPostId,
		});

		const mappedProps = {
			rowsAfter: [],
			independentCapacity: 20,
			totalCapacity: 60,
			sharedCapacity: 40,
			sharedTicketItems: 'items',
		};

		const result = filterCapacityTableMappedProps(mappedProps);
		expect(result).toMatchSnapshot();
	});
});
