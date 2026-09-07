/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPDurationPicker from '../duration-picker/container';
import './style.pcss';

const RSVPV2DurationFields = ( { autosave, hasDurationError } ) => (
	<div className="tribe-editor__rsvp-v2-duration-fields">
		<RSVPDurationPicker
			autosave={ autosave }
			className="tribe-editor__rsvp-duration__duration-picker--no-margin-left tribe-editor__rsvp-duration__duration-picker--sm-label"
			fromLabel={ __( 'Open RSVP:', 'event-tickets' ) }
			toLabel={ __( 'Close RSVP:', 'event-tickets' ) }
			separatorTimeRange=""
		/>
		{ hasDurationError && (
			<span className="tribe-editor__rsvp-v2-duration-fields__error">
				{ __(
					'There is an error with the selected sales duration. Please fix the issue before saving.',
					'event-tickets'
				) }
			</span>
		) }
	</div>
);

RSVPV2DurationFields.propTypes = {
	autosave: PropTypes.bool,
	hasDurationError: PropTypes.bool,
};

export default RSVPV2DurationFields;
