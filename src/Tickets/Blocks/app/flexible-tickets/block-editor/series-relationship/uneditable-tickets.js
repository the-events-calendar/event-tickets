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
} from '../../series-relationship';
import { UNLIMITED } from '@moderntribe/tickets/data/blocks/ticket/constants';

const { setSeriesData } = wp.data.dispatch('tec-tickets/flexible-tickets');

export function updateSeriesData(uneditableTickets = []) {
	const isInSeries = hasSelectedSeries();
	const seriesPasses = uneditableTickets.filter(
		(ticket) => ticket.type === 'series_pass'
	);
	const { independent, shared } = seriesPasses.reduce(
		(acc, ticket) => {
			if (ticket?.capacityType === UNLIMITED) {
				// Unlimited tickets should not be counted in the totals.
				return acc;
			}

			if (ticket.isShared) {
				acc.shared.push(ticket);
			} else {
				acc.independent.push(ticket);
			}
			return acc;
		},
		{ independent: [], shared: [] }
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

	setSeriesData(isInSeries, {
		title: getSeriesTitleFromSelection(),
		editLink: getSeriesEditLinkFromMetaBox(),
		hasSeriesPasses: hasSelectedSeries(),
		passTotalCapacity: sharedCapacity + independentCapacity,
		passTotalAvailable: sharedAvailable + independentAvailable,
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
