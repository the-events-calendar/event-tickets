import types from './types';
import defaultState from './default-state';

export default (state = defaultState, action) => {
	switch (action.type) {
		case types.SET_DEFAULT_TICKET_DESCRIPTION:
			return {
				...state,
				defaultTicketTypeDescriptionTemplate: String(
					action.defaultTicketTypeDescriptionTemplate
				),
			};
		case types.SET_MULTIPLE_PROVIDERS_NOTICE_TEMPLATE:
			return {
				...state,
				multipleProvidersNoticeTemplate: String(
					action.multipleProvidersNoticeTemplate
				),
			};
		case types.SET_SERIES_DATA:
			return {
				...state,
				isInSeries: Boolean(action.isInSeries).valueOf(),
				series: {
					...state.series,
					...action.seriesData,
				},
			};
		default:
			return state;
	}
};
