/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ActionButton } from '../../../../elements';
import { Settings as SettingsIcon } from '../../../../icons';

const SettingsActionButton = ( { isDisabled, onClick = noop } ) => (
	<ActionButton
		className="tribe-editor__rsvp__action-button tribe-editor__rsvp__action-button--settings"
		disabled={ isDisabled }
		id="settings-rsvp"
		icon={ <SettingsIcon /> }
		onClick={ onClick }
	>
		{ __( 'Settings', 'event-tickets' ) }
	</ActionButton>
);

SettingsActionButton.propTypes = {
	isDisabled: PropTypes.bool,
	onClick: PropTypes.func,
};

export default SettingsActionButton;
