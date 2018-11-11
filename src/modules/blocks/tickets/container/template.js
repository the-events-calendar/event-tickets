/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { InnerBlocks } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Availability from '../availability/element';
import { InactiveBlock } from '@moderntribe/tickets/elements';
import { LAYOUT } from '@moderntribe/tickets/elements/inactive-block/element';
import StatusIcon from '@moderntribe/tickets/blocks/ticket/display-container/status-icon/element';
import './style.pcss';

const TicketContainer = ( props ) => {
	const {
		isSelected,
		isEditing,
		total,
		available,
		tickets,
		isLoading,
		isTicketDisabled,
		hasProviders,
	} = props;

	const messages = {
		title: hasProviders
			? __( 'There are no tickets yet', 'events-gutenberg' )
			: __( 'There is no ecommerce available', 'events-gutenberg' ),
		description: hasProviders
			? __( 'Edit this block to create your first ticket.', 'events-gutenberg' )
			: __( 'To create tickets, you\'ll need to enable an ecommerce solution.', 'events-gutenberg' ),
	};

	return (
		<div className="tribe-editor__ticket-container">
			<div className="tribe-editor__tickets-body">
				<InnerBlocks allowedBlocks={ [ 'tribe/tickets-item' ] } />
			</div>
			{ tickets.length === 0 && (
				<InactiveBlock
					layout={ LAYOUT.ticket }
					title={ messages.title }
					description={ messages.description }
					icon={ <StatusIcon disabled={ true } /> }
				/>
			) }
			{ isSelected && ! isEditing && ! isLoading && tickets.length !== 0 && (
				<Availability available={ available } total={ total } isDisabled={ isTicketDisabled } />
			) }
		</div>
	);
};

TicketContainer.propTypes = {
	isSelected: PropTypes.bool,
	isEditing: PropTypes.bool,
	isLoading: PropTypes.bool,
	isTicketDisabled: PropTypes.bool,
	total: PropTypes.number,
	available: PropTypes.number,
	hasProviders: PropTypes.bool,
};

export default TicketContainer;
