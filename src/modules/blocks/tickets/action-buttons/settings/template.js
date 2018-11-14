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
import { ActionButton } from '@moderntribe/tickets/elements';
import { Cog as CogIcon } from '@moderntribe/common/icons';

const SettingsActionButton = ( { onClick } ) => (
	<ActionButton icon={ <CogIcon /> } onClick={ onClick }>
		{ __( 'Settings', 'events-gutenberg' ) }
	</ActionButton>
);

SettingsActionButton.propTypes = {
	onClick: PropTypes.func,
	label: PropTypes.string,
	icon: PropTypes.node,
};

SettingsActionButton.defaultProps = {
	onClick: noop,
};

export default SettingsActionButton;
