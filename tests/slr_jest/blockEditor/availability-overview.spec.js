import { select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { filterSeatedTicketsAvailabilityMappedProps } from '@tec/tickets/seating/blockEditor/availability-overview';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
}));

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
