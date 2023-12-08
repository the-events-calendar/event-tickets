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
		case types.SET_DEFAULT_TICKET_DESCRIPTION:
			return {
				...state,
				defaultTicketTypeDescriptionTemplate: String(
					action.defaultTicketTypeDescriptionTemplate
				),
			};
		case types.SET_SERIES_PASS_TOTAL_CAPACITY:
			return {
				...state,
				series: {
					...state.series,
					passTotalCapacity: action.seriesPassTotalCapacity,
				},
			};
		case types.SET_SERIES_PASS_TOTAL_AVAILABLE:
			return {
				...state,
				series: {
					...state.series,
					passTotalAvailable: action.seriesPassTotalAvailable,
				},
			};
		case types.SET_SERIES_INFORMATION:
			return {
				...state,
				series: {
					...state.series,
					...action.seriesInformation,
				},
			};
		case types.SET_MULTIPLE_PROVIDERS_NOTICE_TEMPLATE:
			return {
				...state,
				multipleProvidersNoticeTemplate: String(
					action.multipleProvidersNoticeTemplate
				),
			};
		default:
			return state;
	}
};
