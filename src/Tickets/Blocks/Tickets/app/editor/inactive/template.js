/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { Card } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

const getCreateSingleTicketMessage = (postTypeLabel) => (
	<div className="tickets-row-line">
		{sprintf(
			// Translators: %1$s the plural, lowercase label for a ticket; %2$s is the post type name in human readable form.
			_x(
				'Create standard %1$s for this %2$s. ',
				'The message displayed when there are no tickets.',
				'event-tickets'
			),
			TICKET_LABELS.ticket.pluralLowercase,
			postTypeLabel ? postTypeLabel : ''
		)}
		<a
			className="helper-link"
			href="https://evnt.is/manage-tickets"
			target="_blank"
			rel="noopener noreferrer"
		>
			{sprintf(
				/* Translators: %s - the singular, lowercase label for a ticket. */
				__('Learn more about %s management', 'event-tickets'),
				TICKET_LABELS.ticket.singularLowercase
			)}
		</a>
	</div>
);

const getInactiveTicketsMessage = ({
	Warning = null,
	allTicketsFuture = false,
	allTicketsPast = false,
	canCreateTickets = true,
	hasCreatedTickets = false,
	hasRecurrenceRules = false,
	postTypeLabel = 'post',
	showWarning = false,
}) => {
	if (!canCreateTickets) {
		return (
			<div className="tribe-editor__title__help-messages">
				<div className="tickets-row-line">
					{sprintf(
						/* Translators: %s - the plural label for a ticket. */
						__(
							"There is no ecommerce available. To create %s, you'll need to enable an ecommerce solution.",
							'event-tickets'
						),
						TICKET_LABELS.ticket.pluralLowercase
					)}
				</div>
			</div>
		);
	}

	if (!hasCreatedTickets) {
		if (!hasRecurrenceRules) {
			return (
				<div className="tribe-editor__title__help-messages">
					{getCreateSingleTicketMessage(postTypeLabel)}
				</div>
			);
		}

		return (
			<div className="tribe-editor__title__help-messages">
				{showWarning ? <Warning /> : null}
			</div>
		);
	}

	if (allTicketsPast || allTicketsFuture) {
		return (
			<div className="tribe-editor__title__help-messages">
				<div className="tickets-row-line">
					{sprintf(
						/* Translators: %1$s - the plural label for a ticket; %2$s - the plural label for a ticket. */
						__(
							'There are no active %1$s. Adjust sale duration to make %2$s available',
							'event-tickets'
						),
						TICKET_LABELS.ticket.pluralLowercase,
						TICKET_LABELS.ticket.pluralLowercase
					)}
				</div>
			</div>
		); // eslint-disable-line max-len
	}

	return (
		<div className="tribe-editor__title__help-messages">
			<div className="tickets-row-line">
				{sprintf(
					/* Translators: %s - the plural label for a ticket. */
					__('%s are not yet available', 'event-tickets'),
					TICKET_LABELS.ticket.plural
				)}
			</div>
		</div>
	);
};

const Inactive = ({
	Warning = null,
	allTicketsFuture = false,
	allTicketsPast = false,
	canCreateTickets = true,
	hasCreatedTickets = false,
	hasRecurrenceRules = false,
	postTypeLabel = 'post',
	showWarning = false,
}) => {
	/**
	 * Filters the components injected before the inactive header of the Tickets block.
	 *
	 * @since 5.20.0
	 *
	 * @return {Array} The injected components.
	 */
	const injectedComponentsTicketsBeforeHeader = applyFilters(
		'tec.tickets.blocks.Tickets.ComponentsBeforeInactiveHeader',
		[]
	);

	return (
		<Card
			className="tribe-editor__card-no-bottom-border"
			header={TICKET_LABELS.ticket.plural}
		>
			{ injectedComponentsTicketsBeforeHeader }
			<div className="tickets-description">
				{getInactiveTicketsMessage({
					Warning,
					allTicketsFuture,
					allTicketsPast,
					canCreateTickets,
					hasCreatedTickets,
					hasRecurrenceRules,
					postTypeLabel,
					showWarning,
				})}
			</div>
		</Card>
	);
};

Inactive.propTypes = {
	Warning: PropTypes.node,
	allTicketsFuture: PropTypes.bool,
	allTicketsPast: PropTypes.bool,
	canCreateTickets: PropTypes.bool,
	hasCreatedTickets: PropTypes.bool,
	hasRecurrenceRules: PropTypes.bool,
	postTypeLabel: PropTypes.string,
	showWarning: PropTypes.bool,
};

export default Inactive;
