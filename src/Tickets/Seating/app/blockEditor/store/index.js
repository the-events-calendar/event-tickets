import { createReduxStore, register } from '@wordpress/data';
import { getTicketIdFromCommonStore } from './common-store-bridge';
import { controls } from './controls';
import { selectors } from './selectors';
import { actions } from './actions';
import { localizedData } from './localized-data';
import { doAction } from '@wordpress/hooks';

const storeName = 'tec-tickets-seating';

// Initialize from the localized object.
const DEFAULT_STATE = {
	...localizedData,
	seatTypesByLayoutId: {},
	seatTypesByClientId: {},
	ticketPostIdByClientId: {},
};

const store = createReduxStore(storeName, {
	reducer(state = DEFAULT_STATE, action) {
		switch (action.type) {
			case 'SET_USING_ASSIGNED_SEATING':
				/**
				 * Fires every time the isUsingAssignedSeating state property is changed.
				 *
				 * @since 5.20.0
				 *
				 * @param {boolean} isUsingAssignedSeating Whether the event is using assigned seating
				 */
				doAction( 'tec.tickets.seating.setUsingAssignedSeating', action.isUsingAssignedSeating );

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

				// Delete the seat type if it's null. Equivalent to the user selecting a blank option.
				if (null === action.seatTypeId) {
					const { seatTypesByClientId, seatTypesByPostId } = state;

					// Remove the seat type for specific client id and post id from the store.
					delete seatTypesByClientId[action.clientId];
					delete seatTypesByPostId[ticketPostId];
					delete seatTypesByPostId[action.clientId];

					return {
						...state,
						seatTypesByClientId,
						seatTypesByPostId,
					};
				}

				return {
					...state,
					seatTypesByClientId: {
						...state.seatTypesByClientId,
						[action.clientId]: action.seatTypeId,
					},
					seatTypesByPostId: {
						...state.seatTypesByPostId,
						[ticketPostId || action.clientId]: action.seatTypeId,
					},
				};
			case 'SET_TICKET_SEAT_TYPE_BY_POST_ID':
				const ticketId = getTicketIdFromCommonStore(action.clientId);

				const { seatTypesByPostId, seatTypesByClientId } = state;

				const seatTypeId =
					seatTypesByClientId[action.clientId] ||
					seatTypesByPostId[ticketId];

				delete seatTypesByPostId[action.clientId];

				return {
					...state,
					seatTypesByPostId: {
						...seatTypesByPostId,
						[ticketId]: seatTypeId,
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
