/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPHeaderImage from '../header-image/container';
import { SettingsDashboard } from '../../../elements';
import { Settings as SettingsIcon } from '../../../icons';
import './style.pcss';

const RSVPSettingsDashboard = ( { isSettingsLoading, onCloseClick } ) => (
	<SettingsDashboard
		className={ classNames( 'tribe-editor__rsvp__settings-dashboard', {
			'tribe-editor__rsvp__settings-dashboard--loading': isSettingsLoading,
		} ) }
		closeButtonDisabled={ isSettingsLoading }
		content={
			<Fragment>
				<RSVPHeaderImage />
				{ isSettingsLoading && <Spinner /> }
			</Fragment>
		}
		headerLeft={
			<Fragment>
				<SettingsIcon />
				<span className="tribe-editor__settings-dashboard__header-left-text">
					{ __( 'RSVP Settings', 'event-tickets' ) }
				</span>
			</Fragment>
		}
		onCloseClick={ onCloseClick }
	/>
);

RSVPSettingsDashboard.propTypes = {
	isSettingsLoading: PropTypes.bool.isRequired,
	onCloseClick: PropTypes.func.isRequired,
};

export default RSVPSettingsDashboard;
