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
import MoveDelete from './move-delete/container';
import './style.pcss';

const confirmLabel = ( hasBeenCreated ) => (
	hasBeenCreated
		? __( 'Update Ticket', 'event-tickets' )
		: __( 'Create Ticket', 'event-tickets' )
);

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
