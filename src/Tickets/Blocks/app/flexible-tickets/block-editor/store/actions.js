import types from './types';

export default {
	setHasSeriesPasses: (hasSeriesPasses) => ({
		type: types.SET_HAS_SERIES_PASSES,
		hasSeriesPasses,
	}),
	setIsInSeries: (isInSeries) => ({
		type: types.SET_IS_IN_SERIES,
		isInSeries,
	}),
	setDefaultTicketTypeDescriptionTemplate: (
		defaultTicketTypeDescriptionTemplate
	) => ({
		type: types.SET_DEFAULT_TICKET_DESCRIPTION,
		defaultTicketTypeDescriptionTemplate,
	}),
	setSeriesPassTotalCapacity: (seriesPassTotalCapacity) => ({
		type: types.SET_SERIES_PASS_TOTAL_CAPACITY,
		seriesPassTotalCapacity,
	}),
	setSeriesPassTotalAvailable: (seriesPassTotalAvailable) => ({
		type: types.SET_SERIES_PASS_TOTAL_AVAILABLE,
		seriesPassTotalAvailable,
	}),
	setSeriesInformation: (seriesInformation) => ({
		type: types.SET_SERIES_INFORMATION,
		seriesInformation,
	}),
};
