import {createReduxStore, register} from '@wordpress/data';

const storeName = 'tec-events-assigned-seating';

const DEFAULT_STATE = {...window.tec.eventsAssignedSeating.blockEditor};

const actions = {
	setUsingAssignedSeating(isUsingAssignedSeating) {
		return {
			type: 'SET_USING_ASSIGNED_SEATING',
			isUsingAssignedSeating,
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
			return state.layouts.map(layout => ({
				label: layout.name,
				value: layout.id,
			}));
		},
		getSeatTypesInOptionFormat(state)  {
			return state.seatTypes.map(seatType => ({
				label: `${seatType.name} (${seatType.seats})`,
				value: seatType.id,
			}));
		},
	},
	controls: {},
	resolvers: {},
});

register(store);

export {store, storeName};