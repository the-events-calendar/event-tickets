/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import noop from 'lodash/noop';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabelWithLink } from '@moderntribe/common/elements';
import './style.pcss';

const helperText = __( 'Save your ticket to enable attendee registration fields', 'events-gutenberg' );

const label = __( 'Attendee Registration', 'events-gutenberg' );

const linkText = __( '+ Add', 'events-gutenberg' );

const AttendeesRegistration = ( {
	attendeeRegistrationURL,
	isCreated,
}) => (
	<div className="tribe-editor__ticket__attendee-registration">
		<LabelWithLink
			className="tribe-editor__ticket__attendee-registration-label-with-link"
			label={ label }
			linkDisabled={ ! isCreated }
			linkHref={ attendeeRegistrationURL }
			linkTarget="_blank"
			linkText={ linkText }
		/>
		{ ! isCreated && (
			<span className="tribe-editor__ticket__attendee-registration-helper-text">
				{ helperText }
			</span>
		) }
	</div>
);

AttendeesRegistration.propTypes = {
	attendeeRegistrationURL: PropTypes.string,
	isCreated: PropTypes.bool,
};

export default AttendeesRegistration;
