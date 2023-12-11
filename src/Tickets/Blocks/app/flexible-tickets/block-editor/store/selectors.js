export default {
	hasSeriesPasses(state) {
		return state.series.hasSeriesPasses;
	},
	isInSeries(state) {
		return state.isInSeries;
	},
	getDefaultTicketTypeDescriptionTemplate(state) {
		return state.defaultTicketTypeDescriptionTemplate;
	},
	getSeriesPassTotalCapacity(state) {
		return state.series.passTotalCapacity;
	},
	getSeriesPassTotalAvailable(state) {
		return state.series.passTotalAvailable;
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
