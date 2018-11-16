/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ActionDashboard } from '@moderntribe/tickets/elements';
import './style.pcss';

const confirmLabel = ( hasBeenCreated ) => (
	hasBeenCreated
		? __( 'Update Ticket', 'events-gutenberg' )
		: __( 'Create Ticket', 'events-gutenberg' )
);

const cancelLabel = __( 'Cancel', 'events-gutenberg' );

const TicketDashboard = ( {
	hasBeenCreated,
	isCancelDisabled,
	isConfirmDisabled,
	onCancelClick,
	onConfirmClick,
} ) => (
	<ActionDashboard
		className="tribe-editor__ticket__dashboard"
		cancelLabel={ cancelLabel }
		confirmLabel={ confirmLabel( hasBeenCreated ) }
		isCancelDisabled={ isCancelDisabled }
		isConfirmDisabled={ isConfirmDisabled }
		onCancelClick={ onCancelClick }
		onConfirmClick={ onConfirmClick }
	/>
);

TicketDashboard.propTypes = {
	created: PropTypes.bool.isRequired,
	isCancelDisabled: PropTypes.bool.isRequired,
	isConfirmDisabled: PropTypes.bool.isRequired,
	onCancelClick: PropTypes.func.isRequired,
	onConfirmClick: PropTypes.func.isRequired,
	showCancel: PropTypes.bool.isRequired,
};

export default TicketDashboard;
