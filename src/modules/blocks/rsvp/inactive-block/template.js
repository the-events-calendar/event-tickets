/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const RSVPInactiveBlock = ({ created }) => {
	const title = created
		? __( 'RSVP is not currently active', 'event-tickets' )
		: __( 'Add an RSVP', 'event-tickets' );

	const description = created
		? __( 'Edit this block to change RSVP settings.', 'event-tickets' )
		: __( 'Allow users to confirm their attendance.', 'event-tickets' );

	return (
		<div class="tribe-common tribe-editor__inactive-block--rsvp">
			<div class="tribe-editor__inactive-block--rsvp__container">
				<div class="tribe-editor__rsvp tribe-common-g-row tribe-common-g-row--gutters">
					<div class="tribe-editor__rsvp-details-wrapper tribe-common-g-col">
						<div class="tribe-editor__rsvp-details">
							<h3 class="tribe-editor__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
								{title}
							</h3>

							<div class="tribe-editor__rsvp-description tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
								{description}
							</div>
						</div>
					</div>

					<div class="tribe-editor__rsvp-actions-wrapper tribe-common-g-col">
						<div class="tribe-editor__rsvp-actions">
							<div class="tribe-editor__rsvp-actions-rsvp">
								<div class="tribe-editor__rsvp-actions-rsvp-create">
									<button class="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium">
										{ __( 'Create RSVP', 'event-tickets' )}
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

RSVPInactiveBlock.propTypes = {
	created: PropTypes.bool.isRequired,
};

export default RSVPInactiveBlock;
