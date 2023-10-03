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
import { Card } from '@moderntribe/tickets/elements';

const RSVPInactiveBlock = ({ created }) => {
	const title = created
		? __( 'RSVP is not currently active', 'event-tickets' )
		: __( 'Add an RSVP', 'event-tickets' );

	const description = created
		? __( 'Edit this block to change RSVP settings.', 'event-tickets' )
		: __( 'Allow users to confirm their attendance.', 'event-tickets' );

	return (
		<Card className="tribe-common tribe-editor__inactive-block--rsvp">
			<div className="tribe-editor__rsvp-details-wrapper">
				<div className="tribe-editor__rsvp-details">
					<h3 className="tribe-editor__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
						{title}
					</h3>

					<div className="tribe-editor__rsvp-description tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
						{description}
					</div>
				</div>
			</div>

			<div className="tribe-editor__rsvp-actions-wrapper">
				<div className="tribe-editor__rsvp-actions">
					<div className="tribe-editor__rsvp-actions-rsvp">
						<div className="tribe-editor__rsvp-actions-rsvp-create">
							<button className="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium">
								{ __( 'Add RSVP', 'event-tickets' )}
							</button>
						</div>
					</div>
				</div>
			</div>
		</Card>
	);
};

RSVPInactiveBlock.propTypes = {
	created: PropTypes.bool.isRequired,
};

export default RSVPInactiveBlock;
