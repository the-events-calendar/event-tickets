/**
 * Following an update that will save the metaboxes values, update the
 * uneditable tickets in the Tickets block, if required and, following their
 * update, update the Series data.
 */

import { updateUneditableTickets } from '@moderntribe/tickets/data/blocks/ticket/actions';
import { store } from '@moderntribe/common/store';
import { onMetaBoxesUpdateCompleted } from '../metaboxes';
import { addAction } from '@wordpress/hooks';
import {
	getSeriesTitleFromSelection,
	getSeriesEditLinkFromMetaBox,
	hasSelectedSeries,
	getSeriesPostIdFromSelection,
} from '../../series-relationship';
import { UNLIMITED } from '@moderntribe/tickets/data/blocks/ticket/constants';
import { sprintf } from '@wordpress/i18n';

const ftStore = wp.data.select('tec-tickets/flexible-tickets');

const { setSeriesData } = wp.data.dispatch('tec-tickets/flexible-tickets');

export function updateSeriesData(uneditableTickets = []) {
	const isInSeries = hasSelectedSeries();
	const seriesPasses = uneditableTickets.filter(
		(ticket) => ticket.type === 'series_pass'
	);
	const { independent, shared, unlimited } = seriesPasses.reduce(
		(acc, ticket) => {
			if (ticket?.capacityType === UNLIMITED) {
				acc.unlimited.push(ticket);
				return acc;
			}

			if (ticket.isShared) {
				acc.shared.push(ticket);
			} else {
				acc.independent.push(ticket);
			}

			return acc;
		},
		{ independent: [], shared: [], unlimited: [] }
	);
	const independentCapacity = independent.reduce(
		(acc, ticket) => acc + (ticket?.capacity || 0),
		0
	);
	const sharedCapacity = shared.reduce(
		(acc, ticket) => Math.max(acc, ticket?.capacity || 0),
		0
	);
	const independentAvailable = independent.reduce(
		(acc, ticket) => acc + (ticket?.available || 0),
		0
	);
	const sharedAvailable = shared.reduce(
		(acc, ticket) => Math.max(acc, ticket?.available || 0),
		0
	);
	const hasUnlimitedSeriesPasses = Boolean(unlimited.length);
	const seriesPostId = getSeriesPostIdFromSelection();
	const seriesPlainUrl = sprintf(
		ftStore.getSeriesHeaderLinkTemplate(),
		seriesPostId
	);

	setSeriesData(isInSeries, {
		title: getSeriesTitleFromSelection(),
		editLink: getSeriesEditLinkFromMetaBox(),
		hasSeriesPasses: Boolean(independent.length || shared.length),
		seriesPassTotalCapacity: sharedCapacity + independentCapacity,
		seriesPassTotalAvailable: sharedAvailable + independentAvailable,
		seriesPassSharedCapacity: sharedCapacity,
		seriesPassIndependentCapacity: independentCapacity,
		seriesPassIndependentCapacityItems: independent
			.map((ticket) => ticket?.title)
			.join(', '),
		seriesPassSharedCapacityItems: shared
			.map((ticket) => ticket?.title)
			.join(', '),
		seriesPassUnlimitedCapacityItems: unlimited
			.map((ticket) => ticket?.title)
			.join(', '),
		hasUnlimitedSeriesPasses,
		headerLink: seriesPlainUrl,
	});
}

// When the uneditble Tickets are updated, update the Series information.
addAction(
	'tec.tickets.blocks.uneditableTicketsUpdated',
	'tec.tickets.flexibleTickets',
	updateSeriesData
);

// When metaboxes are done updating, update the uneditable tickets fetching them from the backend.
onMetaBoxesUpdateCompleted(function () {
	// Tell the Event Tickets store to update the uneditable tickets.
	// After the update this will trigger the `tec.tickets.blocks.uneditableTicketsUpdated` action.
	store.dispatch(updateUneditableTickets());
});
