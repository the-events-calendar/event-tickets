/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ActionDashboard } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import MoveDelete from './move-delete/container';
import './style.pcss';

const confirmLabel = (hasBeenCreated) => {
	return hasBeenCreated
		? // eslint-disable-next-line no-undef
		  sprintf(
				/* Translators: %s - the singular label for a ticket. */
				__('Update %s', 'event-tickets'),
				TICKET_LABELS.ticket.singular
		  )
		: // eslint-disable-next-line no-undef
		  sprintf(
				/* Translators: %s - the singular label for a ticket. */
				__('Create %s', 'event-tickets'),
				TICKET_LABELS.ticket.singular
		  );
};

const cancelLabel = __( 'Cancel', 'event-tickets' );

const TicketDashboard = ( {
	clientId,
	hasBeenCreated,
	isCancelDisabled,
	isConfirmDisabled,
	onCancelClick,
	onConfirmClick,
} ) => (
	<Fragment>
		{ hasBeenCreated && (
			<MoveDelete clientId={ clientId } />
		) }
		<ActionDashboard
			className="tribe-editor__ticket__dashboard tribe-common"
			cancelLabel={ cancelLabel }
			confirmLabel={ confirmLabel( hasBeenCreated ) }
			isCancelDisabled={ isCancelDisabled }
			isConfirmDisabled={ isConfirmDisabled }
			onCancelClick={ onCancelClick }
			onConfirmClick={ onConfirmClick }
		/>
	</Fragment>

);

TicketDashboard.propTypes = {
	clientId: PropTypes.string,
	hasBeenCreated: PropTypes.bool,
	isCancelDisabled: PropTypes.bool,
	isConfirmDisabled: PropTypes.bool,
	onCancelClick: PropTypes.func,
	onConfirmClick: PropTypes.func,
};

export default TicketDashboard;
