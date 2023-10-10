/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.pcss';

const SplitContainer = ({
	leftSide,
	rightSide,
}) => {
	return (
		<>
			<div className="tribe-editor__rsvp-details-wrapper">
				<div className="tribe-editor__rsvp-details">
					{ leftSide }
				</div>
			</div>

			<div className="tribe-editor__rsvp-actions-wrapper">
				<div className="tribe-editor__rsvp-actions">
					<div className="tribe-editor__rsvp-actions-rsvp">
						<div className="tribe-editor__rsvp-actions-rsvp-create">
							{ rightSide }
						</div>
					</div>
				</div>
			</div>
		</>
	);
}

SplitContainer.propTypes = {
	leftSide: PropTypes.node,
	rightSide: PropTypes.node,
};

export default SplitContainer;