/**
 * Mirrors `src/views/v2/commerce/rsvp/details/attendance.php`.
 */
import React from 'react';
import PropTypes from 'prop-types';
import { _x } from '@wordpress/i18n';

const RSVPDetailsAttendance = ( { goingCount } ) => (
	<div className="tribe-tickets__rsvp-attendance">
		<span className="tribe-tickets__rsvp-attendance-number tribe-common-h4 tribe-tickets__rsvp-attendance-number--no-description">
			{ goingCount }
		</span>
		<span className="tribe-tickets__rsvp-attendance-going tribe-common-h7 tribe-common-h--alt tribe-common-b3--min-medium">
			{ _x( 'Going', 'Label below the attendance number', 'event-tickets' ) }
		</span>
	</div>
);

RSVPDetailsAttendance.propTypes = {
	goingCount: PropTypes.number.isRequired,
};

export default RSVPDetailsAttendance;
