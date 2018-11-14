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
import { Counter } from '@moderntribe/common/elements';
import './style.pcss';

const RSVPCounters = ( {
	goingCount,
	notGoingCount,
	showNotGoing,
} ) => (
	<div className="tribe-editor__rsvp-container-header__counters">
		<Counter
			className="tribe-editor__rsvp-container-header__going-counter"
			count={ goingCount }
			label={ __( 'Going', 'events-gutenberg' ) }
		/>
		{ showNotGoing && (
			<Counter
				className="tribe-editor__rsvp-container-header__not-going-counter"
				count={ notGoingCount }
				label={ __( 'Not going', 'events-gutenberg' ) }
			/>
		) }
	</div>
);

RSVPCounters.propTypes = {
	goingCount: PropTypes.number,
	notGoingCount: PropTypes.number,
	showNotGoing: PropTypes.bool,
};

export default RSVPCounters;
