import { createReduxStore, register } from '@wordpress/data';
import { getTicketIdFromCommonStore } from './common-store-bridge';
import { controls } from './controls';
import { selectors } from './selectors';

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
	setEventCapacity(eventCapacity) {
		return {
			type: 'SET_EVENT_CAPACITY',
			eventCapacity,
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
			case 'SET_EVENT_CAPACITY':
				return {
					...state,
					eventCapacity: action.eventCapacity,
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
	selectors,
	controls,
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
