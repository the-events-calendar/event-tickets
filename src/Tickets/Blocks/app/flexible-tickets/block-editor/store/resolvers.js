/**
 * To work-around the fact that different scripts might be localized at different times,
 * we use resolvers to read the information from the actual `window` object when first needed.
 */

import actions from './actions';

export default {
	*hasSeriesPasses() {
		const hasSeriesPasses =
			(window.TECFtEditorData?.series?.seriesPassesCount || 0) > 0;
		return actions.setHasSeriesPasses(hasSeriesPasses);
	},
	*isInSeries() {
		return actions.setIsInSeries(window.tecEventDetails?.isInSeries);
	},
};
