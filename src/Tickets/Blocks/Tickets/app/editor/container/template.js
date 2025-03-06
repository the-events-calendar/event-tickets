/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { applyFilters } from '@wordpress/hooks';

/**
 * Wordpress dependencies
 */
const { InnerBlocks } = wp.blockEditor;

/**
 * Internal dependencies
 */
import Availability from '../availability/container';
import Inactive from '../inactive/container';
import { Card } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
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

	/**
	 * Filters the components injected before the header of the Tickets block.
	 *
	 * @since 5.20.0
	 *
	 * @return {Array} The injected components.
	 */
	const injectedComponentsTicketsBeforeHeader = applyFilters(
		'tec.tickets.blocks.Tickets.ComponentsBeforeHeader',
		[]
	);

	return (
		<div className="tribe-editor__tickets__container">
			<div className={innerBlocksClassName}>
				<Card
					className={cardClassName}
					header={TICKET_LABELS.ticket.plural}
				>
					{ injectedComponentsTicketsBeforeHeader }
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
