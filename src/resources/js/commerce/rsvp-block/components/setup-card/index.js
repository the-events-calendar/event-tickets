/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './styles.pcss';

const SetupCard = ( { leftColumn, rightColumn, className = '' } ) => {
	return (
		<div className={ `tec-rsvp-block__setup-card ${ className }` }>
			<div className="tec-rsvp-block__setup-card-content">
				<div className="tec-rsvp-block__setup-card-left">
					{ leftColumn }
				</div>
				<div className="tec-rsvp-block__setup-card-right">
					{ rightColumn }
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