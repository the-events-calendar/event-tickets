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
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Availability from '../availability/container';
import Inactive from '../inactive/container';
import { Card } from '@moderntribe/tickets/elements';
import './style.pcss';
import Uneditable from '../uneditable/container';

const TicketsOverlay = () => <div className="tribe-editor__tickets__overlay" />;

const TicketsContainer = ({
	Warning,
	canCreateTickets,
	hasATicketSelected,
	hasOverlay,
	isSettingsOpen,
	showAvailability,
	showInactiveBlock,
	showUneditableTickets,
	showWarning,
	uneditableTickets,
	uneditableTicketsAreLoading,
}) => {
	if (isSettingsOpen) {
		return null;
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
					{canCreateTickets && (
						<InnerBlocks allowedBlocks={['tribe/tickets-item']} />
					)}
				</Card>
			</div>

			{showInactiveBlock && !isSettingsOpen && <Inactive />}

			{canCreateTickets &&
				showUneditableTickets &&
				!hasATicketSelected && (
					<>
						{
							<div className="tickets-description">
								<div className="tribe-editor__tickets__container__helper__container">
									{showWarning ? <Warning /> : null}
								</div>
							</div>
						}
						<Uneditable
							loading={uneditableTicketsAreLoading}
							tickets={uneditableTickets}
							cardClassName={uneditableClassName}
						/>
					</>
				)}
			{canCreateTickets && showAvailability && <Availability />}
			{canCreateTickets && hasOverlay && <TicketsOverlay />}
		</div>
	);
};

TicketsContainer.propTypes = {
	Warning: PropTypes.elementType,
	canCreateTickets: PropTypes.bool,
	hasATicketSelected: PropTypes.bool,
	hasOverlay: PropTypes.bool,
	isSettingsOpen: PropTypes.bool,
	showAvailability: PropTypes.bool,
	showInactiveBlock: PropTypes.bool,
	showUneditableTickets: PropTypes.bool,
	showWarning: PropTypes.bool,
	uneditableTickets: PropTypes.arrayOf(PropTypes.object),
	uneditableTicketsAreLoading: PropTypes.bool,
};

export default TicketsContainer;
