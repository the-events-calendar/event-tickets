/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.pcss';

const SplitContainer = ( {
	leftColumn,
	rightColumn,
} ) => {
	return (
		<>
			<div className="tribe-editor__rsvp-details-wrapper">
				<div className="tribe-editor__rsvp-details">
					{ leftColumn }
				</div>
			</div>

			<div className="tribe-editor__rsvp-actions-wrapper">
				<div className="tribe-editor__rsvp-actions">
					<div className="tribe-editor__rsvp-actions-rsvp">
						<div className="tribe-editor__rsvp-actions-rsvp-create">
							{ rightColumn }
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

SplitContainer.propTypes = {
	leftColumn: PropTypes.node,
	rightColumn: PropTypes.node,
};

export default SplitContainer;
