/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import RSVPActionDashboard from '../action-dashboard/container';
import RSVPSettingsDashboard from '../settings-dashboard/container';

const RSVPDashboard = ( { isSelected, isSettingsOpen } ) => {
	if ( ! isSelected ) {
		return null;
	}

	return isSettingsOpen ? <RSVPSettingsDashboard /> : <RSVPActionDashboard />;
};

RSVPDashboard.propTypes = {
	isSelected: PropTypes.bool.isRequired,
	isSettingsOpen: PropTypes.bool.isRequired,
};

export default RSVPDashboard;
