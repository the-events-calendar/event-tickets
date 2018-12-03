/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { InnerBlocks } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Availability from '../availability/container';
import { InactiveBlock } from '@moderntribe/tickets/elements';
import { LAYOUT } from '@moderntribe/tickets/elements/inactive-block/element';
import { TicketInactive } from '@moderntribe/tickets/icons';
import './style.pcss';

const TicketsOverlay = () => <div className="tribe-editor__tickets__overlay" />;

const TicketsContainer = ( {
	hasOverlay,
	hasCreatedTickets,
	hasProviders,
	isSelected,
} ) => {
	const messages = {
		title: hasProviders
			? __( 'There are no tickets yet', 'event-tickets' )
			: __( 'There is no ecommerce available', 'event-tickets' ),
		description: hasProviders
			? __( 'Edit this block to create your first ticket.', 'event-tickets' )
			: __( 'To create tickets, you\'ll need to enable an ecommerce solution.', 'event-tickets' ),
	};

	return (
		<div className="tribe-editor__ticket__container">
			<div className="tribe-editor__tickets__body">
				<InnerBlocks
					allowedBlocks={ [ 'tribe/tickets-item' ] }
					templateLock="insert"
				/>
			</div>
			{ ! hasCreatedTickets && (
				<InactiveBlock
					layout={ LAYOUT.ticket }
					title={ messages.title }
					description={ messages.description }
					icon={ <TicketInactive /> }
				/>
			) }
			{ isSelected && hasCreatedTickets && (
				<Availability />
			) }
			{ hasOverlay && <TicketsOverlay /> }
		</div>
	);
};

TicketsContainer.propTypes = {
	hasCreatedTickets: PropTypes.bool,
	isSelected: PropTypes.bool,
	hasProviders: PropTypes.bool,
};

export default TicketsContainer;
