/**
 * Update Series related information when the Event <> Series relationship is changed.
 */

import { onMetaBoxesUpdateCompleted } from '../metaboxes';
import {
	getSeriesTitleFromSelection,
	getSeriesEditLinkFromMetaBox,
} from '../../series-relationship';

const { setSeriesInformation } = wp.data.dispatch(
	'tec-tickets/flexible-tickets'
);

onMetaBoxesUpdateCompleted(() => {
	setSeriesInformation({
		title: getSeriesTitleFromSelection(),
		editLink: getSeriesEditLinkFromMetaBox(),
	});
});
