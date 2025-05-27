/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import RSVPCapacity from './../capacity/container';
import RSVPDescription from './../description/container';
import RSVPNotGoingResponses from './../not-going/container';
import RSVPTitle from './../title/container';
import RSVPDuration from './../duration/container';
import RSVPAttendeeRegistration from '../attendee-registration/container';
import './style.pcss';

const RSVPContainerContent = ( { isAddEditOpen, hasTicketsPlus } ) => {
	if ( ! isAddEditOpen ) {
		return null;
	}

	return (
		<>
			<RSVPTitle />
			<RSVPDescription />
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
