import { createReduxStore, register } from '@wordpress/data';

const storeName = 'tec-tickets-seating';

const DEFAULT_STATE = { ...window.tec.seating.blockEditor };

const actions = {
	setUsingAssignedSeating(isUsingAssignedSeating) {
		return {
			type: 'SET_USING_ASSIGNED_SEATING',
			isUsingAssignedSeating,
		};
	},
	setLayout(layoutId) {
		return {
			type: 'SET_LAYOUT',
			layoutId,
		};
	},
	setSeatType(ticketBlockClientId, seatTypeId) {
		return {
			type: 'SET_SEAT_TYPE',
			ticketBlockClientId,
			seatTypeId,
		};
	},
};

const store = createReduxStore(storeName, {
	reducer(state = DEFAULT_STATE, action) {
		switch (action.type) {
			case 'SET_USING_ASSIGNED_SEATING':
				return {
					...state,
					isUsingAssignedSeating: action.isUsingAssignedSeating,
				};
			case 'SET_LAYOUT':
				return {
					...state,
					currentLayoutId: action.layoutId,
				};
			case 'SET_SEAT_TYPE':
				return {
					...state,
					seatTypesByTicketId: {
						...state.seatTypesByTicketId,
						[action.ticketBlockClientId]: action.seatTypeId,
					},
				};
		}

		return state;
	},
	actions,
	selectors: {
		isUsingAssignedSeating(state) {
			return state.isUsingAssignedSeating;
		},
		getLayouts(state) {
			return state.layouts;
		},
		getLayoutsInOptionFormat(state) {
			return state.layouts.map((layout) => ({
				label: layout.name,
				value: layout.id,
			}));
		},
		getSeatTypesInOptionFormat(state, layoutId) {
			// @todo fetch this from the backend
			return state.seatTypes.map((seatType) => ({
				label: `${seatType.name} (${seatType.seats})`,
				value: seatType.id,
			}));
		},
		getCurrentLayoutId(state) {
			return state?.currentLayoutId || null;
		},
		getCurrentSeatTypeId(state, ticketBlockClientId) {
			return state?.seatTypes?.[ticketBlockClientId] || null;
		},
	},
	controls: {},
	resolvers: {},
});

register(store);

export { store, storeName };
