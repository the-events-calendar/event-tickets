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

const confirmLabel = __( 'Add Tickets', 'events-gutenberg' );

const TicketsDashboardAction = ( { hasTicketsPlus, onConfirmClick } ) => {
	const actions = hasTicketsPlus
		? [
			<SettingsActionButton />,
			<AttendeesActionButton />,
			<OrdersActionButton />,
		]
		: [
			<SettingsActionButton />,
			<OrdersActionButton />,
		];

		return (
		<ActionDashboard
			className="tribe-editor__tickets__action-dashboard"
			actions={ actions }
			confirmLabel={ confirmLabel }
			onConfirmClick={ onConfirmClick }
			showCancel={ false }
		/>
	);
};

TicketsDashboardAction.propTypes = {
	hasTicketsPlus: PropTypes.bool,
	onConfirmClick: PropTypes.func,
};

export default TicketsDashboardAction;
