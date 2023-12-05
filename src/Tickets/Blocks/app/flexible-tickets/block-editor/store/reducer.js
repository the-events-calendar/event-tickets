import types from './types';
import defaultState from './default-state';

export default (state = defaultState, action) => {
	switch (action.type) {
		case types.SET_HAS_SERIES_PASSES:
			return {
				...state,
				hasSeriesPasses: Boolean(action.hasSeriesPasses).valueOf(),
			};
		case types.SET_IS_IN_SERIES:
			return {
				...state,
				isInSeries: Boolean(action.isInSeries).valueOf(),
			};
		default:
			return state;
	}
};
