/**
 * Mirrors `src/views/v2/commerce/rsvp/details/availability/remaining.php` (editor preview).
 */
import React from 'react';
import PropTypes from 'prop-types';
import { Dashicon } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';

const RSVPDetailsAvailability = ( { available, onEditRemaining, showEditAffordances } ) => {
	const isUnlimited = available === -1;

	const availabilityContent = isUnlimited ? (
		<span className="tribe-tickets__rsvp-availability-unlimited">{ __( 'Unlimited', 'event-tickets' ) }</span>
	) : (
		<>
			<span className="tribe-tickets__rsvp-availability-quantity tribe-common-b2--bold">
				{ ` ${ available } ` }
			</span>
			{ _x( 'remaining', 'Remaining RSVP quantity label', 'event-tickets' ) }
		</>
	);

	return (
		<div className="tribe-tickets__rsvp-availability tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
			{ showEditAffordances ? (
				<button
					className="tribe-editor__rsvp-frontend-mirror__availability-edit"
					onClick={ onEditRemaining }
					type="button"
				>
					{ availabilityContent }
					<Dashicon className="tribe-editor__rsvp-frontend-mirror__edit-icon" icon="edit" />
				</button>
			) : (
				availabilityContent
			) }
		</div>
	);
};

RSVPDetailsAvailability.propTypes = {
	available: PropTypes.number,
	onEditRemaining: PropTypes.func,
	showEditAffordances: PropTypes.bool,
};

export default RSVPDetailsAvailability;
