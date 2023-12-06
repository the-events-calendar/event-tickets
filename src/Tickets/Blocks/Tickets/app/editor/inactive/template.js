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

const getCreateSingleTicketMessage = (postType) => (
	<div className="tickets-row-line">
		{sprintf(
			// Translators: %s is the post type name in human readable form.
			_x(
				'Create single tickets for this %s. ',
				'The message displayed when there are no tickets and has recurrence rules.',
				'event-tickets'
			),
			postType ? postType : ''
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
	allTicketsFuture = false,
	allTicketsPast = false,
	canCreateTickets = true,
	hasCreatedTickets = false,
	hasRecurrenceRules = false,
	showWarning = false,
	Warning = null,
	postType = 'post',
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
					{getCreateSingleTicketMessage(postType)}
				</div>
			);
		}

		return (
			<div className="tribe-editor__title__help-messages">
				{getCreateSingleTicketMessage(postType)}
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

const InactiveTickets = ({
	allTicketsFuture = false,
	allTicketsPast = false,
	canCreateTickets = true,
	hasCreatedTickets = false,
	hasRecurrenceRules = false,
	showWarning = false,
	Warning = null,
}) => {
	return (
		<Card
			className="tribe-editor__card-no-bottom-border"
			header={__('Tickets', 'event-tickets')}
		>
			<div className="tickets-description">
				{getInactiveTicketsMessage({
					allTicketsFuture,
					allTicketsPast,
					canCreateTickets,
					hasCreatedTickets,
					hasRecurrenceRules,
					showWarning,
					Warning,
				})}
			</div>
		</Card>
	);
};

InactiveTickets.propTypes = {
	allTicketsFuture: PropTypes.bool,
	allTicketsPast: PropTypes.bool,
	canCreateTickets: PropTypes.bool,
	hasCreatedTickets: PropTypes.bool,
	hasRecurrenceRules: PropTypes.bool,
	showWarning: PropTypes.bool,
	Warning: PropTypes.node,
	postType: PropTypes.string,
};

export default InactiveTickets;
