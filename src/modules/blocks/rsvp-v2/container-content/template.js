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
import '../../rsvp/container-content/style.pcss';

const RSVPContainerContent = ( { isAddEditOpen, hasTicketsPlus } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	return (
		<>
			<RSVPCapacity />
			<RSVPDuration />
			<RSVPNotGoingResponses />
			{ hasTicketsPlus && <RSVPAttendeeRegistration /> }
		</>
	);
};

RSVPContainerContent.propTypes = {
	isAddEditOpen: PropTypes.bool,
	hasTicketsPlus: PropTypes.bool,
};

export default RSVPContainerContent;
