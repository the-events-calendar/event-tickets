/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import RSVPCapacity from '../../rsvp/capacity/container';
import RSVPNotGoingResponses from '../../rsvp/not-going/container';
import RSVPDuration from '../../rsvp/duration/container';
import RSVPAttendeeRegistration from '../../rsvp/attendee-registration/container';
import RSVPAttendeeCollection from '../../rsvp/attendee-collection/container';
import '../../rsvp/container-content/style.pcss';

const RSVPContainerContent = ( { isAddEditOpen, hasTicketsPlus, hasIacVars } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	return (
		<>
			<RSVPCapacity />
			<RSVPDuration />
			<RSVPNotGoingResponses />
			{ hasTicketsPlus && hasIacVars && <RSVPAttendeeCollection /> }
			{ hasTicketsPlus && <RSVPAttendeeRegistration /> }
		</>
	);
};

RSVPContainerContent.propTypes = {
	isAddEditOpen: PropTypes.bool,
	hasTicketsPlus: PropTypes.bool,
	hasIacVars: PropTypes.bool,
};

export default RSVPContainerContent;
