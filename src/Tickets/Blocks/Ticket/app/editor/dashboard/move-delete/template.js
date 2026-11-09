/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';
import { TICKET_LABELS } from '../../../../../../../modules/data/blocks/ticket/constants';
import { applyFilters } from '@wordpress/hooks';

const RemoveTicketButton = ( { onClick, isDisabled } ) => (
	<Button type="button" onClick={ onClick } disabled={ isDisabled }>
		{ sprintf(
			/* Translators: %s - the singular label for a ticket. */
			__( 'Remove %s', 'event-tickets' ),
			TICKET_LABELS.ticket.singular
		) }
	</Button>
);

const MoveTicketButton = ( { onClick, isDisabled } ) => (
	<Button type="button" onClick={ onClick } disabled={ isDisabled }>
		{ sprintf(
			/* Translators: %s - the singular label for a ticket. */
			__( 'Move %s', 'event-tickets' ),
			TICKET_LABELS.ticket.singular
		) }
	</Button>
);

const MoveDelete = ( { ticketIsSelected, moveTicket, removeTicket, isDisabled, isMoveBlocked, clientId } ) => {
	if ( ! ticketIsSelected ) {
		return null;
	}

	let actions = [
		{
			item: <RemoveTicketButton onClick={ removeTicket } isDisabled={ isDisabled } />,
			key: 'remove',
		},
		{
			item: isMoveBlocked ? (
				<span className="tribe-editor__ticket__move-blocked">
					{ __(
						'Save the post before moving this ticket: it has changes waiting to be saved.',
						'event-tickets'
					) }
				</span>
			) : (
				<MoveTicketButton onClick={ moveTicket } isDisabled={ isDisabled } />
			),
			key: 'move',
		},
	];

	/**
	 * Filters the items to be added to the move/delete section.
	 *
	 * @since 5.16.0
	 *
	 * @param {object[]} actions  An array of action objects.
	 * @param {number}   clientId The client ID of the ticket block.
	 */
	actions = applyFilters( 'tec.tickets.blocks.Ticket.actionItems', actions, clientId );

	return (
		<div className="tribe-editor__ticket__content-row--move-delete">
			{ actions.map( ( action ) => (
				<React.Fragment key={ action.key }>{ action.item }</React.Fragment>
			) ) }
		</div>
	);
};

MoveDelete.propTypes = {
	moveTicket: PropTypes.func.isRequired,
	removeTicket: PropTypes.func.isRequired,
	isDisabled: PropTypes.bool.isRequired,
	isMoveBlocked: PropTypes.bool,
	ticketIsSelected: PropTypes.bool.isRequired,
};

export default MoveDelete;
