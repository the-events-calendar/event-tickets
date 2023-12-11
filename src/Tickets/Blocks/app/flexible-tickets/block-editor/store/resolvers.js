/**
 * To work-around the fact that different scripts might be localized at different times,
 * we use resolvers to read the information from the actual `window` object when first needed.
 */

import actions from './actions';
import {
	getSeriesTitleFromSelection,
	getSeriesEditLinkFromMetaBox,
	hasSelectedSeries,
} from '../../series-relationship';

let didInitSeriesData = false;

const getSeriesData = () => {
	if (didInitSeriesData) {
		return;
	}

	didInitSeriesData = true;
	const isInSeries = hasSelectedSeries();
	const data = {
		title: getSeriesTitleFromSelection(),
		editLink: getSeriesEditLinkFromMetaBox(),
		hasSeriesPasses: window.TECFtEditorData?.series?.seriesPassesCount > 0,
		passTotalCapacity:
			window.TECFtEditorData?.series?.seriesPassTotalCapacity || 0,
		passTotalAvailable:
			window.TECFtEditorData?.series?.seriesPassAvailableCapacity || 0,
	};

	return actions.setSeriesData(isInSeries, data);
};

/*
 * Use the series-related selectors as entry points to fetch the Series information from the page a first time.
 */

export default {
	*hasSeriesPasses() {
		return getSeriesData();
	},
	*isInSeries() {
		return getSeriesData();
	},
	*getSeriesPassTotalCapacity() {
		return getSeriesData();
	},
	*getSeriesPassTotalAvailable() {
		return getSeriesData();
	},
	*getSeriesInformation() {
		return getSeriesData();
	},
	*getDefaultTicketTypeDescriptionTemplate() {
		return actions.setDefaultTicketTypeDescriptionTemplate(
			window.TECFtEditorData
				?.defaultTicketTypeEventInSeriesDescriptionTemplate
		);
	},
	*getMultipleProvidersNoticeTemplate() {
		return actions.setMultipleProvidersNoticeTemplate(
			window?.tribe_editor_config?.tickets
				?.multipleProvidersNoticeTemplate || ''
		);
	},
};
