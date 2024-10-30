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
import RSVPDurationLabel from '@moderntribe/tickets/blocks/rsvp/duration-label/container';
import RSVPDurationPicker from '@moderntribe/tickets/blocks/rsvp/duration-picker/container';
import './style.pcss';

const RSVPDuration = ( { hasDurationError } ) => (
	<div className="tribe-editor__rsvp-duration">
		<RSVPDurationLabel />
		<RSVPDurationPicker />
		{ hasDurationError && (
			<span className="tribe-editor__rsvp-duration__error">
				{ __(
					'There is an error with the selected sales duration. Please fix the issue before saving.', // eslint-disable-line max-len
					'event-tickets',
				) }
			</span>
		) }
	</div>
);

RSVPDuration.propTypes = {
	hasDurationError: PropTypes.bool,
};

export default RSVPDuration;
