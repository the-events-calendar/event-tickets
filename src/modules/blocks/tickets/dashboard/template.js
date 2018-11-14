/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import TicketsActionDashboard from '@moderntribe/tickets/blocks/tickets/action-dashboard/container';
import TicketsSettingsDashboard from '@moderntribe/tickets/blocks/tickets/settings/container';

const TicketsDashboard = ( props ) => {
	const {
		isLoading,
		isSelected,
		isSettingsOpen,
		activeBlockId,
		isEditing,
		clientId,
		isTicketLoading,
	} = props;

	if ( isLoading || isTicketLoading ) {
		return (
			<div className="tribe-editor__tickets-container--loading">
				<Spinner />
			</div>
		);
	}

	if ( ! isSelected ) {
		return null;
	}

	return ( isSettingsOpen
		? <TicketsSettingsDashboard />
		: (
			<TicketsActionDashboard
				activeBlockId={ activeBlockId }
				isEditing={ isEditing }
				clientId={ clientId }
			/>
		) );
}

TicketsDashboard.propTypes = {
	isSelected: PropTypes.bool.isRequired,
	isEditing: PropTypes.bool,
	isSettingsOpen: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool,
	isTicketLoading: PropTypes.bool,
};

TicketsDashboard.defaultProps = {
	isSelected: false,
	isEditing: false,
	isLoading: false,
	isTicketLoading: false,
}

export default TicketsDashboard;
