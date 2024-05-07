import { createReduxStore, register } from '@wordpress/data';

const { fetchSeatTypesByLayoutId } = tec.seating.ajax;

const storeName = 'tec-tickets-seating';

// Initialize from the localized object.
const DEFAULT_STATE = {
	...window.tec.seating.blockEditor,
	seatTypesByLayoutId: {},
};

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
	setSeatTypesForLayout(layoutId, seatTypes) {
		return {
			type: 'SET_SEAT_TYPES_FOR_LAYOUT',
			layoutId,
			seatTypes,
		};
	},
	fetchSeatTypesForLayout(layoutId) {
		return {
			type: 'FETCH_SEAT_TYPES_FOR_LAYOUT',
			layoutId,
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
			case 'SET_SEAT_TYPES_FOR_LAYOUT':
				return {
					...state,
					seatTypesByLayoutId: {
						...state.seatTypesByLayoutId,
						[action.layoutId]: action.seatTypes,
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
		getSeatTypesForLayout(state, layoutId) {
			const layoutSeatTypes =
				state.seatTypesByLayoutId?.[layoutId] || null;

			if (!layoutSeatTypes) {
				return [];
			}

			return layoutSeatTypes.map((seatType) => ({
				label: `${seatType.name} (${seatType.seats})`,
				value: seatType.id,
			}));
		},
		getCurrentLayoutId(state) {
			return state?.currentLayoutId || null;
		},
	},
	controls: {
		FETCH_SEAT_TYPES_FOR_LAYOUT(action) {
			return fetchSeatTypesByLayoutId(action.layoutId);
		},
	},
	resolvers: {
		*getSeatTypesForLayout(layoutId) {
			if (!layoutId) {
				return null;
			}

			const seatTypes = yield actions.fetchSeatTypesForLayout(layoutId);
			return actions.setSeatTypesForLayout(layoutId, seatTypes);
		},
	},
});

register(store);

export { store, storeName };
