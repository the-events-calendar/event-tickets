import { createReduxStore, register } from '@wordpress/data';
import { getTicketIdFromCommonStore } from './common-store-bridge';

const { fetchSeatTypesByLayoutId } = tec.tickets.seating.ajax;

const storeName = 'tec-tickets-seating';

// Initialize from the localized object.
const DEFAULT_STATE = {
	...window.tec.tickets.seating.blockEditor,
	seatTypesByLayoutId: {},
	seatTypesByClientId: {},
	ticketPostIdByClientId: {},
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
	setTicketSeatType(clientId, seatTypeId) {
		return {
			type: 'SET_TICKET_SEAT_TYPE',
			clientId,
			seatTypeId,
		};
	},
	fetchSeatTypesForLayout(layoutId) {
		return {
			type: 'FETCH_SEAT_TYPES_FOR_LAYOUT',
			layoutId,
		};
	},
	setIsLayoutLocked(isLayoutLocked){
		return {
			type: 'LOCK_LAYOUT',
			isLayoutLocked,
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
				const reduceSeatTypes = action.seatTypes.reduce(
					(carry, seatType) => ({
						...carry,
						[seatType.id]: seatType,
					}),
					{}
				);
				return {
					...state,
					seatTypesByLayoutId: {
						...state.seatTypesByLayoutId,
						[action.layoutId]: reduceSeatTypes,
					},
				};
			case 'SET_TICKET_SEAT_TYPE':
				const ticketPostId = getTicketIdFromCommonStore(
					action.clientId
				);
				return {
					...state,
					seatTypesByClientId: {
						...state.seatTypesByClientId,
						[action.clientId]: action.seatTypeId,
					},
					seatTypesByPostId: {
						...state.seatTypesByPostId,
						[ticketPostId]: action.seatTypeId,
					},
				};
			case 'LOCK_LAYOUT':
				return {
					...state,
					isLayoutLocked: action.isLayoutLocked,
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
		getLayoutSeats(state, layoutId) {
			return (
				state.layouts.find((layout) => layout.id === layoutId)?.seats ||
				0
			);
		},
		getLayoutsInOptionFormat(state) {
			return state.layouts.map((layout) => ({
				label: layout.name,
				value: layout.id,
			}));
		},
		getSeatTypesForLayout(state, layoutId, onlyValue = false) {
			const layoutSeatTypes =
				state.seatTypesByLayoutId?.[layoutId] || null;

			if (!layoutSeatTypes) {
				return [];
			}

			if ( onlyValue ) {
				return layoutSeatTypes;
			}

			return Object.values(layoutSeatTypes).map(function (seatType) {
				return {
					label: `${seatType.name} (${seatType.seats})`,
					value: seatType.id,
				};
			});
		},
		getCurrentLayoutId(state) {
			return state?.currentLayoutId || null;
		},
		getSeatTypeSeats(state, seatTypeId) {
			return (
				state?.seatTypesByLayoutId?.[state.currentLayoutId]?.[
					seatTypeId
				]?.seats || 0
			);
		},
		getTicketSeatType(state, clientId) {
			const ticketPostId = getTicketIdFromCommonStore(clientId);

			return (
				state?.seatTypesByPostId?.[ticketPostId] ||
				state?.seatTypesByClientId?.[clientId] ||
				null
			);
		},
		isLayoutLocked(state) {
			return state?.isLayoutLocked || false;
		},
		getAllSeatTypes(state) {
			return state?.seatTypes || [];
		},
		getSeatTypesByPostID(state) {
			return state?.seatTypesByPostId || [];
		}
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
