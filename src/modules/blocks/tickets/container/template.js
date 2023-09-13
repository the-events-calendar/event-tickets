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
import InactiveTicket from '@moderntribe/tickets/blocks/tickets/inactive/inactive';
import './style.pcss';

const TicketsOverlay = () => <div className="tribe-editor__tickets__overlay" />;

const TicketsContainer = ( {
	allTicketsPast,
	canCreateTickets,
	clientId,
	hasCreatedTickets,
	hasOverlay,
	isSelected,
	isSettingsOpen,
	showAvailability,
	showInactiveBlock,
} ) => {
	const messages = {
		title: '',
		description: '',
	};

	if ( ! canCreateTickets ) {
		messages.title = __( 'There is no ecommerce available', 'event-tickets' );
		messages.description = __(
			'To create tickets, you\'ll need to enable an ecommerce solution.',
			'event-tickets',
		);
	} else if ( ! hasCreatedTickets ) {
		messages.title = __( 'There are no tickets yet', 'event-tickets' );
		messages.description = __( 'Edit this block to create your first ticket.', 'event-tickets' );
	} else if ( allTicketsPast ) {
		messages.title = __( 'Tickets are no longer available', 'event-tickets' );
	} else {
		messages.title = __( 'Tickets are not yet available', 'event-tickets' );
	}

	const innerBlocksClassName = classNames( {
		'tribe-editor__tickets__inner-blocks': true,
		'tribe-editor__tickets__inner-blocks--show': ! showInactiveBlock,
	} );

	return (
		<div className="tribe-editor__tickets__container">
			<div className={ innerBlocksClassName }>
				<InnerBlocks
					allowedBlocks={ [ 'tribe/tickets-item' ] }
				/>
			</div>
			{
				showInactiveBlock && ! isSettingsOpen && (
					<InactiveTicket />
				)
			}
			{ showAvailability && <Availability /> }
			{ hasOverlay && <TicketsOverlay /> }
		</div>
	);
};

TicketsContainer.propTypes = {
	allTicketsPast: PropTypes.bool,
	canCreateTickets: PropTypes.bool,
	hasCreatedTickets: PropTypes.bool,
	hasOverlay: PropTypes.bool,
	isSelected: PropTypes.bool,
	isSettingsOpen: PropTypes.bool,
	showAvailability: PropTypes.bool,
	showInactiveBlock: PropTypes.bool,
};

export default TicketsContainer;
