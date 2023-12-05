import types from './types';

export default {
	setHasSeriesPasses(hasSeriesPasses) {
		return {
			type: types.SET_HAS_SERIES_PASSES,
			hasSeriesPasses,
		};
	},
	setIsInSeries: (isInSeries) => {
		return {
			type: types.SET_IS_IN_SERIES,
			isInSeries,
		};
	},
};
