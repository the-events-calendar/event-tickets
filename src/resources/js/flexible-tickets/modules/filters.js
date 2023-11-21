/**
 * This file contains all the functions that are used to filter the event block editor
 * in its various parts to support the Flexible Tickets feature.
 */

/**
 * Prevents Series Passes from being saved by the Block Editor when editing Events.
 *
 * @since TBD
 *
 * @param {bool} saveTicketFromPost Whether or not to save the Ticket from the Post.
 * @param {number} id The ID of the Ticket.
 * @param {string} ticketType The ticket types, e.g. `default`, `series_pass`, etc.
 * @param {object} post The Post object that is being saved, the format is the one retruned by the WP REST API.
 *
 * @returns {boolean} Whether or not to save the Ticket from the Post.
 */
function doNotEditSeriesPassesOutsideSeries( saveTicketFromPost, id, ticketType, post ) {
	const postType = post?.type;

	if ( !( typeof ticketType === 'string' && typeof postType === 'string' ) ) {
		return saveTicketFromPost;
	}

	if ( ticketType === 'series_pass' && postType !== 'tribe_event_series' ) {
		return false;
	}

	return saveTicketFromPost;
}

// Series Passes will appear in the tickets list of Events, but they should not be editable from Events.
wp.hooks.addFilter (
	'tec.tickets.blocks.editTicketFromPost',
	'tec.tickets.flexibleTickets',
	doNotEditSeriesPassesOutsideSeries
);

/**
 * Filters the ticket type description when creating a ticket of the `default` type for an Event part of a Series.
 *
 * @since TBD
 *
 * @param {Object} mappedProps The properties mapped from the state for the Ticket Type component.
 * @param {Object} ticketDetails The ticket details.
 *
 * @returns {string} The ticket type description, possibly changed.
 */
function changeTicketTypeDescriptionForEventPartOfSeries( mappedProps, { ticketDetails } ) {
	const ticketType = ticketDetails?.type || 'default';
	const isInSeries = tecEventDetails?.isInSeries;

	if ( !( isInSeries && ticketType === 'default' ) ) {
		return mappedProps;
	}

	const description = TECFtEditorData?.defaultTicketTypeEventInSeriesDescription;
	if ( description ) {
		mappedProps.typeDescription = description;
	}

	return mappedProps;
}

// Change the description of default tickets when editing an Event part of a Series.
wp.hooks.addFilter (
	'tec.tickets.blocks.ticket.Type.mappedProps',
	'tec.tickets.flexibleTickets',
	changeTicketTypeDescriptionForEventPartOfSeries
);
