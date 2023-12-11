import types from './types';

export default {
	setDefaultTicketTypeDescriptionTemplate: (
		defaultTicketTypeDescriptionTemplate
	) => ({
		type: types.SET_DEFAULT_TICKET_DESCRIPTION,
		defaultTicketTypeDescriptionTemplate,
	}),
	setMultipleProvidersNoticeTemplate: (multipleProvidersNoticeTemplate) => ({
		type: types.SET_MULTIPLE_PROVIDERS_NOTICE_TEMPLATE,
		multipleProvidersNoticeTemplate,
	}),
	setSeriesData: (isInSeries, seriesData) => ({
		type: types.SET_SERIES_DATA,
		isInSeries,
		seriesData,
	}),
};
