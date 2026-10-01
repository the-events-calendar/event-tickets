/**
 * Mirrors `src/views/v2/commerce/rsvp/details.php`.
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import RSVPDetailsTitle from './details-title';
import RSVPDetailsAttendance from './details-attendance';
import RSVPDetailsAvailability from './details-availability';
import { AttendeesActionButton } from '../../../rsvp-shared/action-buttons';

const RSVPDetails = ( {
	available,
	detailsRef,
	goingCount,
	notGoingCount,
	onEditRemaining,
	showEditAffordances,
	showNotGoing,
	title,
} ) => (
	<div className="tribe-tickets__rsvp-details-wrapper tribe-common-g-col">
		<div className="tribe-tickets__rsvp-details" ref={ detailsRef }>
			<RSVPDetailsTitle title={ title } />
			<div className="tribe-editor__rsvp-frontend-mirror__going-row">
				<RSVPDetailsAttendance goingCount={ goingCount } />
				<AttendeesActionButton />
			</div>
			<RSVPDetailsAvailability
				available={ available }
				onEditRemaining={ onEditRemaining }
				showEditAffordances={ showEditAffordances }
			/>
			{ showNotGoing && (
				<div className="tribe-editor__rsvp-frontend-mirror__not-going-count tribe-common-b3--min-medium">
					<span className="tribe-editor__rsvp-frontend-mirror__not-going-quantity">
						{ notGoingCount }
					</span>
					{ ' ' }
					{ __( 'Not going', 'event-tickets' ) }
				</div>
			) }
		</div>
	</div>
);

RSVPDetails.propTypes = {
	available: PropTypes.number,
	detailsRef: PropTypes.shape( { current: PropTypes.instanceOf( Element ) } ),
	goingCount: PropTypes.number.isRequired,
	notGoingCount: PropTypes.number,
	onEditRemaining: PropTypes.func,
	showEditAffordances: PropTypes.bool,
	showNotGoing: PropTypes.bool,
	title: PropTypes.string.isRequired,
};

export default RSVPDetails;
