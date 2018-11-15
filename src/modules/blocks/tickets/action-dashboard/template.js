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

const confirmLabel = ( created ) => (
	created
		? __( 'Update Ticket', 'events-gutenberg' )
		: __( 'Create Ticket', 'events-gutenberg' )
);

const TicketsDashboardAction = ( props ) => {
	const {
		onConfirmClick,
		isEditing,
		isEditFormValid,
		hasBeenCreated,
		onCancelClick,
		hasProviders,
		hasTicketsPlus,
	} = props;

	const dashboardProps = {
		actions: [],
		confirmLabel: __( 'Add Tickets', 'events-gutenberg' ),
		onConfirmClick,
		onCancelClick,
	};

	if ( ! isEditing ) {
		if ( hasTicketsPlus ) {
			dashboardProps.actions = actions;
		} else {
			dashboardProps.actions = [
				<SettingsActionButton />,
				<OrdersActionButton />,
			];
		}
	}

	/**
	 * @todo: Remove the dependency of the current/active child block on this dashboard and move the
	 * editing dashboard into each child ticket instead.
	 *
	 * For a more detail explanation of what's required here take a look at:
	 * - https://github.com/moderntribe/events-gutenberg/pull/336#discussion_r221192383
	 */
	if ( isEditing ) {
		dashboardProps.isConfirmDisabled = ! isEditFormValid;
		dashboardProps.cancelLabel = __( 'Cancel', 'events-gutenberg' );
		dashboardProps.confirmLabel = confirmLabel( hasBeenCreated );
	} else {
		dashboardProps.showConfirm = hasProviders;
	}

	return (
		<ActionDashboard { ...dashboardProps } />
	);
}

TicketsDashboardAction.propTypes = {
	created: PropTypes.bool,
	isEditing: PropTypes.bool,
	isEditFormValid: PropTypes.bool,
	activeBlockId: PropTypes.string,
	hasBeenCreated: PropTypes.bool,
	hasProviders: PropTypes.bool,
	hasTicketsPlus: PropTypes.bool,
};

export default TicketsDashboardAction;
