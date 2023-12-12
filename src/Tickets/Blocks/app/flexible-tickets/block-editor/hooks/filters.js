/**
 * This file contains all the functions that are used to filter the event block editor
 * in its various parts to support the Flexible Tickets feature.
 */

import { addFilter } from '@wordpress/hooks';
import SeriesPassNotice from '../components/series-pass-notice/container';
import { sprintf } from '@wordpress/i18n';
import { renderToString } from '@wordpress/element';

/**
 * Pull the Flexible Tickets data from the dedicated store.
 */
const ftStore = wp.data.select('tec-tickets/flexible-tickets');

/**
 * Prevents Series Passes from being saved by the Block Editor when editing Events.
 *
 * @since TBD
 *
 * @param {boolean} saveTicketFromPost Whether or not to save the Ticket from the Post.
 * @param {Object}  context            The context of the filter.
 * @param {string}  context.ticketType The ticket types, e.g. `default`, `series_pass`, etc.
 * @param {Object}  context.post       The Post object that is being saved, the format is the one retruned by the WP REST API.
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
	const isInSeries = ftStore.isInSeries();

	if (!(isInSeries && ticketType === 'default')) {
		return mappedProps;
	}

	const { title: seriesTitle } = ftStore.getSeriesInformation();
	const newDescription = sprintf(
		ftStore.getDefaultTicketTypeDescriptionTemplate(),
		seriesTitle
	);
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
 * @param {Object}  mappedProps                      The properties mapped from the state for the Tickets component.
 * @param {boolean} mappedProps.noTicketsOnRecurring Whether or not to show the Tickets block on Recurring Events.
 * @param {boolean} mappedProps.canCreateTickets     Whether or not the user can create tickets.
 * @param {Object}  context                          The context of the filter.
 * @param {Object}  context.ownProps                 The props passed to the block.
 * @param {boolean} context.ownProps.isSelected      Whether or not the block is selected.
 *
 * @return {Object} The modified properties mapped from the state for the Tickets component.
 */
function filterTicketsMappedProps(mappedProps, { ownProps: { isSelected } }) {
	const isInSeries = ftStore.isInSeries();
	const canCreateTickets = mappedProps?.canCreateTickets;

	if (!(isInSeries && canCreateTickets)) {
		return mappedProps;
	}

	const showWarning = getShowWarning(mappedProps, isSelected);

	if (showWarning) {
		mappedProps.showWarning = showWarning;
		mappedProps.Warning = SeriesPassNotice;
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
 * @param {Object}  mappedProps                    The properties mapped from the state for the TicketsContainer component.
 * @param {boolean} mappedProps.showInactiveBlock  Whether or not to show the inactive block.
 * @param {boolean} mappedProps.showAvailability   Whether or not to show the availability.
 * @param {boolean} mappedProps.hasRecurrenceRules Whether or not the Event has recurrence rules.
 * @param {boolean} ownProps.isSelected            Whether or not the block is selected.
 * @param {boolean} mappedProps.showWarning        Whether or not the Event has a warning to display.
 * @param {Object}  mappedProps.Warning            Warning component to be displayed in case there is one.
 */
function filterTicketsContainerMappedProps(
	mappedProps,
	{ ownProps: { isSelected = false } }
) {
	const isInSeries = ftStore.isInSeries();

	if (!isInSeries) {
		return mappedProps;
	}

	const showWarning = getShowWarning(mappedProps, isSelected);

	if (showWarning) {
		mappedProps.showWarning = showWarning;
		mappedProps.Warning = SeriesPassNotice;
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

/**
 * @param {boolean} mappedProps.hasCreatedTickets  Whether or not the user has created tickets.
 * @param {boolean} mappedProps.hasRecurrenceRules Whether or not the Event has recurrence rules.
 * @param {boolean} mappedProps.hasCreatedTickets  Whether or not the user has created tickets.
 * @param           mappedProps
 * @param {boolean} isSelected                     Whether or not the block is selected.
 * @return {boolean}  Flag indicating whether or not to display the warning.
 */
function getShowWarning(mappedProps, isSelected) {
	const hasSeriesPasses = ftStore.hasSeriesPasses();

	let showWarning = false;

	if (!mappedProps.hasCreatedTickets && isSelected) {
		showWarning = true;
	} else if (mappedProps.hasCreatedTickets && hasSeriesPasses && isSelected) {
		showWarning = true;
	} else if (
		!mappedProps.hasCreatedTickets &&
		!hasSeriesPasses &&
		!isSelected
	) {
		showWarning = true;
	}

	return showWarning;
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
 * @param {Object}  mappedProps                   The properties mapped from the state for the TicketsDashboardAction component.
 * @param {boolean} mappedProps.showWarning       Whether or not to show the warning.
 * @param {boolean} mappedProps.disableSettings   Whether or not to disable the settings.
 * @param {boolean} mappedProps.hasCreatedTickets Whether or not the user has created tickets.
 * @param {boolean} mappedProps.hasOrdersPage     Whether or not the user has an Orders page.
 * @param {boolean} mappedProps.showConfirm       Whether or not to show the confirmation button.
 * @param {Object}  context                       The context of the filter.
 * @param {Object}  context.isRecurring           Whether or not the Event is currently recurring.
 *
 * @return {Object} The modified properties mapped from the state for the TicketsDashboardAction component.
 */
function filterTicketsDashboardActionsMappedProps(
	mappedProps,
	{ isRecurring }
) {
	const isInSeries = ftStore.isInSeries();

	if (!isInSeries) {
		return mappedProps;
	}

	mappedProps.showWarning = isRecurring;
	mappedProps.disableSettings = true;
	const hasSeriesPasses = ftStore.hasSeriesPasses();
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
	const seriesCapacity = ftStore.getSeriesPassTotalCapacity();
	const seriesAvailability = ftStore.getSeriesPassTotalAvailable();
	const isInSeries = ftStore.isInSeries();

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

function filterTicketsControlsMappedProps(mappedProps) {
	const isInSeries = ftStore.isInSeries();

	if (!isInSeries) {
		return mappedProps;
	}

	mappedProps.disabled = true;
	const { title: seriesTitle, editLink: seriesEditLink } =
		ftStore.getSeriesInformation();
	const link = (
		<a
			target="_blank"
			href={seriesEditLink + '#tribetickets'}
			rel="noreferrer"
		>
			{seriesTitle}
		</a>
	);
	const messageTemplate = ftStore.getMultipleProvidersNoticeTemplate();
	mappedProps.message = (
		<p
			dangerouslySetInnerHTML={{
				__html: sprintf(messageTemplate, renderToString(link)),
			}}
		></p>
	);

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.Controls.mappedProps',
	'tec.tickets.flexibleTickets',
	filterTicketsControlsMappedProps
);

function filterUneditableMappedProps(mappedProps) {
	if (!mappedProps?.cardsByTicketType?.series_pass) {
		return mappedProps;
	}

	const link = ftStore.getSeriesHeaderLink();
	const message = ftStore.getSeriesHeaderLinkText();
	mappedProps.cardsByTicketType.series_pass.description = (
		<a
			href={link}
			target="_blank"
			rel="noreferrer"
			className="tickets-heading__description__link"
		>
			{message}
		</a>
	);

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.Uneditable.mappedProps',
	'tec.tickets.flexibleTickets',
	filterUneditableMappedProps
);
