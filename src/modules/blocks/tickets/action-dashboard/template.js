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
import {
	SettingsActionButton,
	AttendeesActionButton,
	OrdersActionButton,
} from '@moderntribe/tickets/blocks/tickets/action-buttons';

const actions = [
	<SettingsActionButton />,
	<AttendeesActionButton />,
	<OrdersActionButton />,
];

const confirmLabel = __( 'Add Tickets', 'events-gutenberg' );

const TicketsDashboardAction = ( { onConfirmClick } ) => (
	<ActionDashboard
		className="tribe-editor__tickets__action-dashboard"
		actions={ actions }
		confirmLabel={ confirmLabel }
		onConfirmClick={ onConfirmClick }
		showCancel={ false }
	/>
);

TicketsDashboardAction.propTypes = {
	onConfirmClick: PropTypes.func,
};

export default TicketsDashboardAction;
