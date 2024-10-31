import { selectors } from '@tec/tickets/seating/blockEditor/store/selectors';
import compatibility from '@tec/tickets/seating/blockEditor/store/compatibility';
jest.mock('@tec/tickets/seating/blockEditor/store/compatibility', () => ({
	currentProviderSupportsSeating: jest.fn(()=>true),
}));
jest.mock('@tec/tickets/seating/blockEditor/store/common-store-bridge', () => ({
	getTicketIdFromCommonStore: (clientId) => {
		if ('client-id-1' === clientId) {
			return 24;
		}

		if ('client-id-2' === clientId) {
			return 56;
		}

		return 0;
	},
}));

const state = {
	isUsingAssignedSeating: true,
	layouts: [
		{
			id: 'layout-1',
			name: 'Layout 1',
			seats: 10,
		},
		{
			id: 'layout-2',
			name: 'Layout 2',
			seats: 20,
		},
	],
	seatTypes: [
		{
			id: 'seat-type-1',
			name: 'Seat Type 1',
			seats: 5,
		},
		{
			id: 'seat-type-2',
			name: 'Seat Type 2',
			seats: 6,
		},
		{
			id: 'seat-type-3',
			name: 'Seat Type 3',
			seats: 8,
		},
		{
			id: 'seat-type-4',
			name: 'Seat Type 4',
			seats: 10,
		},
		{
			id: 'seat-type-5',
			name: 'Seat Type 5',
			seats: 3,
		},
		{
			id: 'seat-type-6',
			name: 'Seat Type 6',
			seats: 7,
		},
	],
	seatTypesByLayoutId: {
		'layout-1': {
			'seat-type-1': {
				id: 'seat-type-1',
				name: 'Seat Type 1',
				seats: 5,
			},
			'seat-type-2': {
				id: 'seat-type-2',
				name: 'Seat Type 2',
				seats: 6,
			},
			'seat-type-3': {
				id: 'seat-type-3',
				name: 'Seat Type 3',
				seats: 8,
			},
		},
		'layout-2': {
			'seat-type-4': {
				id: 'seat-type-4',
				name: 'Seat Type 4',
				seats: 10,
			},
			'seat-type-5': {
				id: 'seat-type-5',
				name: 'Seat Type 5',
				seats: 3,
			},
			'seat-type-6': {
				id: 'seat-type-6',
				name: 'Seat Type 6',
				seats: 7,
			},
		},
	},
	currentLayoutId: 'layout-1',
	seatTypesByPostId: {
		24: 'seat-type-1',
		56: 'seat-type-4',
		78: 'seat-type-5',
	},
	seatTypesByClientId: {
		'client-id-1': 'seat-type-1',
		'client-id-2': 'seat-type-4',
		'client-id-3': 'seat-type-5',
	},
	isLayoutLocked: true,
};

describe('selectors', () => {
	beforeEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	afterEach(() => {
		jest.resetModules();
		jest.resetAllMocks();
	});

	describe('isUsingAssignedSeating', () => {
		it('should return isUsingAssignedSeating value', () => {
			compatibility.currentProviderSupportsSeating.mockReturnValue(true);

			expect(selectors.isUsingAssignedSeating(state)).toBe(true);
			state.isUsingAssignedSeating = false;
			expect(selectors.isUsingAssignedSeating(state)).toBe(false);
			state.isUsingAssignedSeating = true;
		});

		it('should return false if current provider does not support seating', () => {
			state.isUsingAssignedSeating = true;
			compatibility.currentProviderSupportsSeating.mockReturnValue(true);

			expect(selectors.isUsingAssignedSeating(state)).toBe(true);

			compatibility.currentProviderSupportsSeating.mockReturnValue(false);

			expect(selectors.isUsingAssignedSeating(state)).toBe(false);

			state.isUsingAssignedSeating = true;

			expect(selectors.isUsingAssignedSeating(state)).toBe(false);
		});
	});

	describe('getLayouts', () => {
		it('should return layouts', () => {
			expect(selectors.getLayouts(state)).toBe(state.layouts);
		});
	});

	describe('getLayoutSeats', () => {
		it('should return the amount seats for each layoutid', () => {
			for (const layout of state.layouts) {
				expect(selectors.getLayoutSeats(state, layout.id)).toBe(
					layout.seats
				);
			}
		});
	});

	describe('getLayoutsInOptionFormat', () => {
		it('should return the layouts in an option format', () => {
			expect(selectors.getLayoutsInOptionFormat(state)).toMatchSnapshot();
		});
	});

	describe('getSeatTypesForLayout', () => {
		it('should return the seat types per layout', () => {
			for (const layout of state.layouts) {
				expect(
					selectors.getSeatTypesForLayout(state, layout.id, true)
				).toBe(state.seatTypesByLayoutId[layout.id]);
				expect(
					selectors.getSeatTypesForLayout(state, layout.id, false)
				).toStrictEqual(
					Object.values(state.seatTypesByLayoutId[layout.id]).map(
						(seatType) => {
							return {
								label: `${seatType.name} (${seatType.seats})`,
								value: seatType.id,
							};
						}
					)
				);
			}

			expect(
				selectors.getSeatTypesForLayout(state, 'unknown', true)
			).toStrictEqual([]);
			expect(
				selectors.getSeatTypesForLayout(state, 'unknown')
			).toStrictEqual([]);
		});
	});

	describe('getCurrentLayoutId', () => {
		it('should return the current layout id if seating is enabled', () => {
			expect(selectors.getCurrentLayoutId(state)).toBe(
				state.currentLayoutId
			);
			state.isUsingAssignedSeating = false;
			expect(selectors.getCurrentLayoutId(state)).toBe(null);
			state.isUsingAssignedSeating = true;
		});
	});

	describe('getSeatTypeSeats', () => {
		it('should return the amount of seats per seat type if seating is enabled', () => {
			for (const seatType in state.seatTypesByLayoutId['layout-1']) {
				expect(
					selectors.getSeatTypeSeats(
						state,
						state.seatTypesByLayoutId['layout-1'][seatType].id
					)
				).toBe(state.seatTypesByLayoutId['layout-1'][seatType].seats);
			}

			expect(selectors.getSeatTypeSeats(state, 'unknown')).toBe(0);

			state.isUsingAssignedSeating = false;
			expect(selectors.getSeatTypeSeats(state, 'unknown')).toBe(null);
			state.isUsingAssignedSeating = true;
		});
	});

	describe('getTicketSeatType', () => {
		it('should return the seat type by client or post id if seating is enabled', () => {
			expect(selectors.getTicketSeatType(state, 'client-id-1')).toBe(
				'seat-type-1'
			);
			expect(selectors.getTicketSeatType(state, 'client-id-2')).toBe(
				'seat-type-4'
			);

			expect(selectors.getTicketSeatType(state, 'client-id-3')).toBe(
				'seat-type-5'
			);

			expect(selectors.getTicketSeatType(state, 'unknown')).toBe(null);

			state.isUsingAssignedSeating = false;
			expect(selectors.getTicketSeatType(state, 'client-id-1')).toBe(
				null
			);
			state.isUsingAssignedSeating = true;
		});
	});

	describe('isLayoutLocked', () => {
		it('should return isLayoutLocked value', () => {
			expect(selectors.isLayoutLocked(state)).toBe(true);
			state.isLayoutLocked = false;
			expect(selectors.isLayoutLocked(state)).toBe(false);
			state.isLayoutLocked = true;
		});
	});

	describe('getAllSeatTypes', () => {
		it('should match snapshot', () => {
			expect(selectors.getAllSeatTypes(state)).toMatchSnapshot();
		});
	});

	describe('getEventCapacity', () => {
		it('should return getEventCapacity value or 0', () => {
			expect(selectors.getEventCapacity(state)).toBe(0);
			state.eventCapacity = 76;
			expect(selectors.getEventCapacity(state)).toBe(76);
		});
	});

	describe('getSeatTypesByPostID', () => {
		it('should return seat types by post id when seating is enabled', () => {
			expect(selectors.getSeatTypesByPostID(state)).toBe(
				state.seatTypesByPostId
			);

			state.isUsingAssignedSeating = false;
			expect(selectors.getSeatTypesByPostID(state)).toBe(null);
			state.isUsingAssignedSeating = true;
		});
	});

	describe('getSeatTypesByClientID', () => {
		it('should return seat types by client id when seating is enabled', () => {
			expect(selectors.getSeatTypesByClientID(state)).toBe(
				state.seatTypesByClientId
			);

			state.isUsingAssignedSeating = false;
			expect(selectors.getSeatTypesByClientID(state)).toBe(null);
			state.isUsingAssignedSeating = true;
		});
	});
});
