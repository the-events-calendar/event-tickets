import { select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { filterTicketIsAsc } from '@tec/tickets/seating/blockEditor/ticket-is-asc';

jest.mock('@wordpress/data', () => ({
	select: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
}));

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
