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
import { ActionButton } from '@moderntribe/tickets/elements';
import { Settings } from '@moderntribe/tickets/icons';

const SettingsActionButton = ( { onClick } ) => (
	<ActionButton icon={ <Settings /> } onClick={ onClick }>
		{ __( 'Settings', 'event-tickets' ) }
	</ActionButton>
);

SettingsActionButton.propTypes = {
	onClick: PropTypes.func,
};

export default SettingsActionButton;
