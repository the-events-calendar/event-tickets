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
import RSVPCapacity from '../../rsvp/capacity/template';

const RSVPV2Capacity = ( props ) => (
	<RSVPCapacity
		{ ...props }
		helpText={ __( 'Leave blank for unlimited', 'event-tickets' ) }
		label={ __( 'Limit:', 'event-tickets' ) }
	/>
);

RSVPV2Capacity.propTypes = {
	isDisabled: PropTypes.bool,
	onTempCapacityChange: PropTypes.func.isRequired,
	tempCapacity: PropTypes.string,
};

export default RSVPV2Capacity;
