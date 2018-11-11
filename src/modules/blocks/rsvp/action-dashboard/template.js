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
import {
	SettingsActionButton,
	AttendeesActionButton,
} from '@moderntribe/tickets/blocks/rsvp/action-buttons';
import { ActionDashboard } from '@moderntribe/tickets/elements';

const actions = [
	<SettingsActionButton />,
	<AttendeesActionButton />,
];

const confirmLabel = ( created ) => (
	created
		? __( 'Update RSVP', 'events-gutenberg' )
		: __( 'Create RSVP', 'events-gutenberg' )
);

const cancelLabel = __( 'Cancel', 'events-gutenberg' );

const RSVPActionDashboard = ( {
	created,
	isCancelDisabled,
	isConfirmDisabled,
	onCancelClick,
	onConfirmClick,
	showCancel,
} ) => (
	<ActionDashboard
		className="tribe-editor__rsvp__action-dashboard"
		actions={ actions }
		cancelLabel={ cancelLabel }
		confirmLabel={ confirmLabel( created ) }
		isCancelDisabled={ isCancelDisabled }
		isConfirmDisabled={ isConfirmDisabled }
		onCancelClick={ onCancelClick }
		onConfirmClick={ onConfirmClick }
		showCancel={ showCancel }
	/>
);

RSVPActionDashboard.propTypes = {
	created: PropTypes.bool.isRequired,
	isCancelDisabled: PropTypes.bool.isRequired,
	isConfirmDisabled: PropTypes.bool.isRequired,
	onCancelClick: PropTypes.func.isRequired,
	onConfirmClick: PropTypes.func.isRequired,
	showCancel: PropTypes.bool.isRequired,
};

export default RSVPActionDashboard;
