/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './styles.pcss';
import { __ } from "@wordpress/i18n";

const SetupCard = ( { setIsSettingUp, className = '' } ) => {
	return (
		<div className={ `tribe-editor__card tribe-common tribe-editor__inactive-block--rsvp tec-rsvp-block__setup-card ${ className }` }>
			<div className="tribe-editor__rsvp-details-wrapper">
				<div className="tribe-editor__rsvp-details">
					<h3 className="tec-rsvp-block__setup-title tribe-common-h2 tribe-common-h4--min-medium">
						{ __( 'Add an RSVP', 'event-tickets' ) }
					</h3>
					<div className="tribe-editor__rsvp-description tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
						{ __( 'Allow users to confirm their attendance.', 'event-tickets' ) }
					</div>
				</div>
			</div>
			<div className="tribe-editor__rsvp-actions-wrapper">
				<div className="tribe-editor__rsvp-actions">
					<div className="tribe-editor__rsvp-actions-rsvp">
						<div className="tribe-editor__rsvp-actions-rsvp-create">
							<Button
								id="add-rsvp"
								variant="primary"
								size="large"
								className="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium"
								onClick={ () => setIsSettingUp( true ) }
							>
								{ __( 'Add RSVP', 'event-tickets' ) }
							</Button>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

SetupCard.propTypes = {
	leftColumn: PropTypes.node,
	rightColumn: PropTypes.node,
	className: PropTypes.string,
};

export default SetupCard;
