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

const TicketContainer = ( {
	hasOverlay,
	hasTickets,
	hasProviders,
	isSelected,
} ) => {
	const messages = {
		title: hasProviders
			? __( 'There are no tickets yet', 'events-gutenberg' )
			: __( 'There is no ecommerce available', 'events-gutenberg' ),
		description: hasProviders
			? __( 'Edit this block to create your first ticket.', 'events-gutenberg' )
			: __( 'To create tickets, you\'ll need to enable an ecommerce solution.', 'events-gutenberg' ),
	};

	return (
		<div className="tribe-editor__ticket__container">
			<div className="tribe-editor__tickets__body">
				<InnerBlocks allowedBlocks={ [ 'tribe/tickets-item' ] } />
			</div>
			{ ! hasTickets && (
				<InactiveBlock
					layout={ LAYOUT.ticket }
					title={ messages.title }
					description={ messages.description }
					icon={ <TicketInactive /> }
				/>
			) }
			{ isSelected && hasTickets && (
				<Availability />
			) }
			{ hasOverlay && <TicketsOverlay /> }
		</div>
	);
};

TicketContainer.propTypes = {
	hasTickets: PropTypes.bool,
	isSelected: PropTypes.bool,
	hasProviders: PropTypes.bool,
};

export default TicketContainer;
