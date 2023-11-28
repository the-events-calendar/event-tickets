/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
const { InnerBlocks } = wp.blockEditor;
import { __, _x, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Availability from '../availability/container';
import InactiveTicket from '../inactive/inactive';
import { Card } from '@moderntribe/tickets/elements';
import './style.pcss';
import Uneditable from '../uneditable/container';

const TicketsOverlay = () => <div className="tribe-editor__tickets__overlay" />;

const TicketsContainer = ({
	allTicketsFuture,
	allTicketsPast,
	canCreateTickets,
	hasATicketSelected,
	hasCreatedTickets,
	hasOverlay,
	hasRecurrenceRules,
	isSettingsOpen,
	postType,
	showAvailability,
	showInactiveBlock,
	showUneditableTickets,
	showWarning,
	tickets,
	Warning,
}) => {
	const messages = {
		title: '',
		description: '',
	};

	if (isSettingsOpen) {
		return null;
	}

	if (!canCreateTickets) {
		messages.title = (
			<div className="tribe-editor__title__help-messages">
				<div className="tickets-row-line">
					{ __('There is no ecommerce available', 'event-tickets') }
				</div>
			</div>
		);
		messages.description = __(
			"To create tickets, you'll need to enable an ecommerce solution.",
			'event-tickets'
		);
	} else if (!hasCreatedTickets) {
		if (!hasRecurrenceRules) {
			messages.title = (
				<div className="tribe-editor__title__help-messages">
					<div className="tickets-row-line">
						{
							_x(
								'Add a ticket to get started.',
								'The message displayed when there are no tickets and no recurrence rules.',
								'event-tickets'
							)
						}
					</div>
				</div>
			);
		} else {
			messages.title = (
				<div className="tribe-editor__title__help-messages">
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
							{__(
								'Learn more about ticket management',
								'event-tickets'
							)}
						</a>
					</div>
					{ showWarning ? <Warning /> : null }
				</div>
			);
		}

		messages.description = __(
			'Edit this block to create your first ticket.',
			'event-tickets'
		);
	} else if (allTicketsPast || allTicketsFuture) {
		messages.title = (
			<div className="tribe-editor__title__help-messages">
				<div className="tickets-row-line">
					{
						__(
							'There are no active tickets. Adjust sale duration to make tickets available',
							'event-tickets'
						)
					}
				</div>
			</div>
		); // eslint-disable-line max-len
	} else {
		messages.title = __('Tickets are not yet available', 'event-tickets');
	}

	const innerBlocksClassName = classNames({
		'tribe-editor__tickets__inner-blocks': true,
		'tribe-editor__tickets__inner-blocks--show': !showInactiveBlock,
	});

	const cardClassName = classNames({
		'tribe-editor__card-no-bottom-border': !hasATicketSelected,
		'tribe-editor__card-padding-bottom': hasATicketSelected,
	});

	const uneditableClassName = classNames({
		'tribe-editor__card-no-bottom-border': !hasATicketSelected,
		'tribe-editor__card-no-top-border': !hasATicketSelected,
		'tribe-editor__card-padding-bottom': hasATicketSelected,
	});

	return (
		<div className="tribe-editor__tickets__container">
			<div className={innerBlocksClassName}>
				<Card
					className={cardClassName}
					header={__('Tickets', 'event-tickets')}
				>
					<InnerBlocks allowedBlocks={['tribe/tickets-item']} />
				</Card>
			</div>
			{showInactiveBlock && !isSettingsOpen && (
				<InactiveTicket title={messages.title} />
			)}
			{ showUneditableTickets && ! hasATicketSelected && hasCreatedTickets && (
				<>
					{
						<div className="tickets-description">
							<div className="tribe-editor__tickets__container__helper__container">
								{ showWarning ? <Warning /> : null }
							</div>
						</div>
					}
					<Uneditable
						tickets={tickets}
						cardClassName={uneditableClassName}
					/>
				</>
			) }
			{showAvailability && <Availability />}
			{hasOverlay && <TicketsOverlay />}
		</div>
	);
};

TicketsContainer.propTypes = {
	allTicketsFuture: PropTypes.bool,
	allTicketsPast: PropTypes.bool,
	canCreateTickets: PropTypes.bool,
	hasATicketSelected: PropTypes.bool,
	hasCreatedTickets: PropTypes.bool,
	hasOverlay: PropTypes.bool,
	isSettingsOpen: PropTypes.bool,
	postType: PropTypes.string,
	seriesName: PropTypes.string,
	seriesPassLink: PropTypes.string,
	showAvailability: PropTypes.bool,
	showInactiveBlock: PropTypes.bool,
	showUneditableTickets: PropTypes.bool,
	tickets: PropTypes.arrayOf(PropTypes.object),
};

export default TicketsContainer;
