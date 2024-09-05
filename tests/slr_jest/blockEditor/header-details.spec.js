import { select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { filterHeaderDetails } from '@tec/tickets/seating/blockEditor/header-details';
import SeatType from '@tec/tickets/seating/blockEditor/header/seat-type';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
}));

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
