export default {
	hasSeriesPasses(state) {
		return state.hasSeriesPasses;
	},
	isInSeries(state) {
		return state.isInSeries;
	},
	getDefaultTicketTypeDescriptionTemplate(state) {
		return state.defaultTicketTypeDescriptionTemplate;
	},
	getSeriesPassTotalCapacity(state) {
		return state.seriesPassTotalCapacity;
	},
	getSeriesPassTotalAvailable(state) {
		return state.seriesPassTotalAvailable;
	},
	getSeriesInformation(state) {
		return {
			title: state.series.title,
			editLink: state.series.editLink,
		};
	},
	getMultipleProvidersNoticeTemplate(state) {
		return state.multipleProvidersNoticeTemplate;
	},
};
