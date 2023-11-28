/**
 * This file contains all the functions that are used to filter the event block editor
 * in its various parts to support the Flexible Tickets feature.
 */

import { addFilter } from '@wordpress/hooks';

/**
 * Prevents Series Passes from being saved by the Block Editor when editing Events.
 *
 * @since TBD
 *
 * @param {bool}   saveTicketFromPost Whether or not to save the Ticket from the Post.
 * @param {Object} context            The context of the filter.
 * @param {string} context.ticketType The ticket types, e.g. `default`, `series_pass`, etc.
 * @param {Object} context.post       The Post object that is being saved, the format is the one retruned by the WP REST API.
 *
 * @return {boolean} Whether or not to save the Ticket from the Post.
 */
function doNotEditSeriesPassesOutsideSeries(
	saveTicketFromPost,
	{ ticketType, post }
) {
	const postType = post?.type;

	if (!(typeof ticketType === 'string' && typeof postType === 'string')) {
		return saveTicketFromPost;
	}

	if (ticketType === 'series_pass' && postType !== 'tribe_event_series') {
		return false;
	}

	return saveTicketFromPost;
}

// Series Passes will appear in the tickets list of Events, but they should not be editable from Events.
addFilter(
	'tec.tickets.blocks.editTicketFromPost',
	'tec.tickets.flexibleTickets',
	doNotEditSeriesPassesOutsideSeries
);

/**
 * Filters the ticket type description when creating a ticket of the `default` type for an Event part of a Series.
 *
 * @since TBD
 *
 * @param {Object} mappedProps                 The properties mapped from the state for the Ticket Type component.
 * @param {string} mappedProps.typeDescription The ticket type description.
 * @param          ticketDetails.ticketDetails
 * @param {Object} ticketDetails               The ticket details.
 *
 * @return {Object} The modified properties mapped from the state for the Ticket Type component.
 */
function changeTicketTypeDescriptionForEventPartOfSeries(
	mappedProps,
	{ ticketDetails }
) {
	const ticketType = ticketDetails?.type || 'default';
	const isInSeries = tecEventDetails?.isInSeries;

	if (!(isInSeries && ticketType === 'default')) {
		return mappedProps;
	}

	const newDescription =
		TECFtEditorData?.defaultTicketTypeEventInSeriesDescription;
	mappedProps.typeDescription = newDescription || mappedProps.typeDescription;

	return mappedProps;
}

// Change the description of default tickets when editing an Event part of a Series.
addFilter(
	'tec.tickets.blocks.Tickets.Type.mappedProps',
	'tec.tickets.flexibleTickets',
	changeTicketTypeDescriptionForEventPartOfSeries
);

/**
 * Forces the Tickets block to show on Recurring Events if they are part of a Series.
 *
 * @since TBD
 *
 * @param {Object} mappedProps                      The properties mapped from the state for the Tickets component.
 * @param {bool}   mappedProps.noTicketsOnRecurring Whether or not to show the Tickets block on Recurring Events.
 *
 * @return {Object} The modified properties mapped from the state for the Tickets component.
 */
function filterTicketsMappedProps(mappedProps) {
	const isInSeries = tecEventDetails?.isInSeries;

	if (!isInSeries) {
		return mappedProps;
	}

	mappedProps.noTicketsOnRecurring = false;

	return mappedProps;
}

// Do show the Tickets block on recurring events if they are part of a series.
addFilter(
	'tec.tickets.blocks.Tickets.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsMappedProps
);

/**
 * Modifies the properties mapped from the state for the TicketsContainer component to conform
 * to the Flexible Tickets feature.
 *
 * @since TBD
 *
 * @param {Object} mappedProps                    The properties mapped from the state for the TicketsContainer component.
 * @param {bool}   mappedProps.showInactiveBlock  Whether or not to show the inactive block.
 * @param {bool}   mappedProps.showAvailability   Whether or not to show the availability.
 * @param {bool}   mappedProps.hasRecurrenceRules Whether or not the Event has recurrence rules.
 * @param {bool}   ownProps.isSelected            Whether or not the block is selected.
 */
function filterTicketsContainerMappedProps(
	mappedProps,
	{ ownProps: { isSelected = false } }
) {
	const isInSeries = tecEventDetails?.isInSeries;

	if (!isInSeries) {
		return mappedProps;
	}

	const hasRecurrenceRules = mappedProps.hasRecurrenceRules;
	mappedProps.canCreateTickets = hasRecurrenceRules
		? false
		: mappedProps.canCreateTickets;
	mappedProps.showInactiveBlock = hasRecurrenceRules
		? false
		: mappedProps.showInactiveBlock;
	mappedProps.showAvailability = isSelected;

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsContainerMappedProps
);

/**
 * Modifies the properties mapped from the state for the TicketsDashboardAction component to conform
 * to the Flexible Tickets feature.
 *
 * @since TBD
 *
 * @param {Object} mappedProps                   The properties mapped from the state for the TicketsDashboardAction component.
 * @param {bool}   mappedProps.showWarning       Whether or not to show the warning.
 * @param {bool}   mappedProps.disableSettings   Whether or not to disable the settings.
 * @param {bool}   mappedProps.hasCreatedTickets Whether or not the user has created tickets.
 * @param {bool}   mappedProps.hasOrdersPage     Whether or not the user has an Orders page.
 * @param {bool}   mappedProps.showConfirm       Whether or not to show the confirmation button.
 * @param {Object} context                       The context of the filter.
 * @param {Object} context.isRecurring           Whether or not the Event is currently recurring.
 *
 * @return {Object} The modified properties mapped from the state for the TicketsDashboardAction component.
 */
function filterTicketsDashboardActionsMappedProps(
	mappedProps,
	{ isRecurring }
) {
	const isInSeries = tecEventDetails?.isInSeries;

	if (!isInSeries) {
		return mappedProps;
	}

	mappedProps.showWarning = false;
	mappedProps.disableSettings = true;
	const hasSeriesPasses =
		(TECFtEditorData?.series?.seriesPassesCount || 0) > 0;
	mappedProps.hasCreatedTickets = hasSeriesPasses;
	mappedProps.hasOrdersPage = hasSeriesPasses;
	mappedProps.showConfirm = !isRecurring;

	return mappedProps;
}

// Do not show the warning on Recurring Events if they are part of a Series.
addFilter(
	'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsDashboardActionsMappedProps
);

/**
 * Modifies the properties mapped from the state for the Availability component to conform
 * to the Flexible Tickets feature.
 *
 * @since TBD
 *
 * @param {Object} mappedProps           The properties mapped from the state for the Availability component.
 * @param {number} mappedProps.total     The total capacity.
 * @param {number} mappedProps.available The available capacity.
 */
function filterTicketsAvailabilityMappedProps(mappedProps) {
	const currentCapacity = mappedProps?.total || 0;
	const currentAvailability = mappedProps?.available || 0;
	const seriesCapacity =
		TECFtEditorData?.series?.seriesPassTotalCapacity || 0;
	const seriesAvailability =
		TECFtEditorData?.series?.seriesPassAvailableCapacity || 0;
	const isInSeries = tecEventDetails?.isInSeries;

	if (isInSeries && seriesCapacity >= 0) {
		mappedProps.total = currentCapacity + seriesCapacity;
		mappedProps.available = currentAvailability + seriesAvailability;
	}

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.Availability.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsAvailabilityMappedProps
);

function filterTicketsControlsMappedProps(
	mappedProps,
	{ isRecurring = false }
) {
	const isInSeries = tecEventDetails?.isInSeries;

	if (!isInSeries) {
		return mappedProps;
	}

	mappedProps.disabled = isRecurring;

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.Controls.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsControlsMappedProps
);
