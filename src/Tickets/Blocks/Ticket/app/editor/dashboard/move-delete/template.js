/**
 * External Dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

const MoveDelete = ( {
	ticketIsSelected,
	moveTicket,
	removeTicket,
	isDisabled,
} ) => {
	if ( ! ticketIsSelected ) {
		return null;
	}

	return (
		<div className="tribe-editor__ticket__content-row--move-delete">
			<Button type="button" onClick={ removeTicket } disabled={ isDisabled }>
				{
					// eslint-disable-next-line no-undef
					sprintf(
						/* Translators: %s - the singular label for a ticket. */
						__('Remove %s', 'event-tickets'),
						tribe_editor_config.tickets.ticketLabels.ticket.singular // eslint-disable-line camelcase, no-undef
					)
				}
			</Button>
			<Button type="button" onClick={ moveTicket } disabled={ isDisabled }>
				{
					// eslint-disable-next-line no-undef
					sprintf(
						/* Translators: %s - the singular label for a ticket. */
						__('Move %s', 'event-tickets'),
						tribe_editor_config.tickets.ticketLabels.ticket.singular // eslint-disable-line camelcase, no-undef
					)
				}
			</Button>
		</div>
	);
};

MoveDelete.propTypes = {
	moveTicket: PropTypes.func.isRequired,
	removeTicket: PropTypes.func.isRequired,
	isDisabled: PropTypes.bool.isRequired,
	ticketIsSelected: PropTypes.bool.isRequired,
};

export default MoveDelete;
