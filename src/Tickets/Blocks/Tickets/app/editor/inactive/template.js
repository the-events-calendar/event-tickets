/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * Wordpress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Card } from '@moderntribe/tickets/elements';

const getCreateSingleTicketMessage = (postTypeLabel) => (
	<div className="tickets-row-line">
		{sprintf(
			// Translators: %s is the post type name in human readable form.
			_x(
				'Create standard tickets for this %s. ',
				'The message displayed when there are no tickets.',
				'event-tickets'
			),
			postTypeLabel ? postTypeLabel : ''
		)}
		<a
			className="helper-link"
			href="https://evnt.is/manage-tickets"
			target="_blank"
			rel="noopener noreferrer"
		>
			{__('Learn more about ticket management', 'event-tickets')}
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
					{__(
						"There is no ecommerce available. To create tickets, you'll need to enable an ecommerce solution.",
						'event-tickets'
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
					{__(
						'There are no active tickets. Adjust sale duration to make tickets available',
						'event-tickets'
					)}
				</div>
			</div>
		); // eslint-disable-line max-len
	}

	return (
		<div className="tribe-editor__title__help-messages">
			<div className="tickets-row-line">
				{__('Tickets are not yet available', 'event-tickets')}
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
	return (
		<Card
			className="tribe-editor__card-no-bottom-border"
			header={__('Tickets', 'event-tickets')}
		>
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
