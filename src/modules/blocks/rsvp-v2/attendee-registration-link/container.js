/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPAttendeeRegistration from '../../rsvp-shared/attendee-registration/container';

/**
 * The V2 attendee registration link wraps the shared attendee registration
 * container, which owns the full iframe modal logic (overlay, unload
 * listeners, IAC sync bridge). The V2 RSVP data layer re-exports the shared
 * selectors and actions, so the shared container connects to the V2 state
 * tree directly. Only the V2 link copy differs from the shared default.
 */
const RSVPAttendeeRegistrationLink = () => (
	<RSVPAttendeeRegistration
		label=""
		linkTextAdd={ __( '+ Collect attendee information', 'event-tickets' ) }
		linkTextEdit={ __( 'Edit attendee information', 'event-tickets' ) }
	/>
);

export default RSVPAttendeeRegistrationLink;
