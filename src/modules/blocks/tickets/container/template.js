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
import { Card } from '@moderntribe/tickets/elements';
import './style.pcss';

const TicketsOverlay = () => <div className="tribe-editor__tickets__overlay" />;

const TicketsContainer = ( {
	allTicketsFuture,
	allTicketsPast,
	canCreateTickets,
	hasCreatedTickets,
	hasOverlay,
	isSettingsOpen,
	showAvailability,
	showInactiveBlock,
	hasATicketSelected,
} ) => {
	const messages = {
		title: '',
		description: '',
	};

	if ( isSettingsOpen ) {
		return null;
	}

	if ( ! canCreateTickets ) {
		messages.title = __( 'There is no ecommerce available', 'event-tickets' );
		messages.description = __(
			'To create tickets, you\'ll need to enable an ecommerce solution.',
			'event-tickets',
		);
	} else if ( ! hasCreatedTickets ) {
		messages.title = __( 'Add a ticket to get started.', 'event-tickets' );
		messages.description = __( 'Edit this block to create your first ticket.', 'event-tickets' );
	} else if ( allTicketsPast || allTicketsFuture ) {
		messages.title = __( 'There are no active tickets. Adjust sale duration to make tickets available', 'event-tickets' ); // eslint-disable-line max-len
	} else {
		messages.title = __( 'Tickets are not yet available', 'event-tickets' );
	}

	const innerBlocksClassName = classNames( {
		'tribe-editor__tickets__inner-blocks': true,
		'tribe-editor__tickets__inner-blocks--show': ! showInactiveBlock,
	} );

	const cardClassName = classNames( {
		'tribe-editor__card-no-bottom-border': ! hasATicketSelected,
		'tribe-editor__card-padding-bottom': hasATicketSelected,
	} );

	return (
		<div className="tribe-editor__tickets__container">
			<div className={ innerBlocksClassName }>
				<Card
					className={ cardClassName }
					header={ __( 'Tickets', 'event-tickets' ) }
				>
					<InnerBlocks
						allowedBlocks={ [ 'tribe/tickets-item' ] }
					/>
				</Card>
			</div>
			{
				showInactiveBlock && ! isSettingsOpen && (
					<InactiveTicket
						title={ messages.title }
					/>
				)
			}
			{ showAvailability && <Availability /> }
			{ hasOverlay && <TicketsOverlay /> }
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
	showAvailability: PropTypes.bool,
	showInactiveBlock: PropTypes.bool,
};

export default TicketsContainer;
