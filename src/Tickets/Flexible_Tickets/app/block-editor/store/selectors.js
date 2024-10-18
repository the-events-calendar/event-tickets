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
		return state.series.seriesPassTotalCapacity;
	},
	getSeriesPassTotalAvailable(state) {
		return state.series.seriesPassTotalAvailable;
	},
	getSeriesHeaderLink(state) {
		return state.series.headerLink;
	},
	getSeriesHeaderLinkTemplate(state) {
		return state.series.headerLinkTemplate;
	},
	getSeriesHeaderLinkText(state) {
		return state.series.headerLinkText;
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
	getSeriesPassSharedCapacity(state) {
		return state.series.seriesPassSharedCapacity;
	},
	getSeriesPassIndependentCapacity(state) {
		return state.series.seriesPassIndependentCapacity;
	},
	getSeriesPassSharedCapacityItems(state) {
		return state.series.seriesPassSharedCapacityItems;
	},
	getSeriesPassIndependentCapacityItems(state) {
		return state.series.seriesPassIndependentCapacityItems;
	},
	getSeriesPassUnlimitedCapacityItems(state) {
		return state.series.seriesPassUnlimitedCapacityItems;
	},
	hasUnlimitedSeriesPasses(state) {
		return state.series.hasUnlimitedSeriesPasses;
	},
	getLabels(state) {
		return state.labels;
	},
};
