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
import RSVPV2Capacity from '../capacity/container';
import RSVPV2DurationFields from '../duration-fields/container';
import RSVPAttendeeRegistrationLink from '../attendee-registration-link/container';
import './style.pcss';

const RSVPCreateForm = ( { clientId } ) => (
	<div className="tribe-editor__rsvp-v2-create-form">
		<h3 className="tribe-editor__rsvp-v2-create-form__title tribe-common-h3 tribe-common-h4--min-medium">
			{ __( 'Add RSVP', 'event-tickets' ) }
		</h3>
		<RSVPV2Capacity />
		<RSVPV2DurationFields />
		<div className="tribe-editor__rsvp-v2-attendee-registration-link">
			<RSVPAttendeeRegistrationLink clientId={ clientId } />
		</div>
	</div>
);

RSVPCreateForm.propTypes = {
	clientId: PropTypes.string,
};

export default RSVPCreateForm;
