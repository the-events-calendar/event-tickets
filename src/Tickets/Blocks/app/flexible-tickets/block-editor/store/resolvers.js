/**
 * To work-around the fact that different scripts might be localized at different times,
 * we use resolvers to read the information from the actual `window` object when first needed.
 */

import actions from './actions';
import {
	getSeriesTitleFromSelection,
	getSeriesEditLinkFromMetaBox,
} from '../../series-relationship';

export default {
	*hasSeriesPasses() {
		const hasSeriesPasses =
			(window.TECFtEditorData?.series?.seriesPassesCount || 0) > 0;
		return actions.setHasSeriesPasses(hasSeriesPasses);
	},
	*isInSeries() {
		return actions.setIsInSeries(window.tecEventDetails?.isInSeries);
	},
	*getDefaultTicketTypeDescription() {
		return actions.setDefaultTicketTypeDescription(
			window.TECFtEditorData?.defaultTicketTypeEventInSeriesDescription
		);
	},
	*getSeriesPassTotalCapacity() {
		return actions.setSeriesPassTotalCapacity(
			window.TECFtEditorData?.series?.seriesPassTotalCapacity || 0
		);
	},
	*getSeriesPassTotalAvailable() {
		return actions.setSeriesPassTotalAvailable(
			window.TECFtEditorData?.series?.seriesPassAvailableCapacity || 0
		);
	},
	*getSeriesInformation() {
		return actions.setSeriesInformation({
			title: getSeriesTitleFromSelection(),
			editLink: getSeriesEditLinkFromMetaBox(),
		});
	},
};
