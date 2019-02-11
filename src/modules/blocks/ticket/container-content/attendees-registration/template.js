/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabelWithLink } from '@moderntribe/common/elements';
import './style.pcss';

const helperText = __( 'Save your ticket to enable attendee registration fields', 'event-tickets' );
const label = __( 'Attendee Registration', 'event-tickets' );
const linkTextAdd = __( '+ Add', 'event-tickets' );
const linkTextEdit = __( 'Edit', 'event-tickets' );

const AttendeesRegistration = ( {
	attendeeRegistrationURL,
	hasAttendeeInfoFields,
	isCreated,
	isDisabled,
}) => {
	const linkText = hasAttendeeInfoFields ? linkTextEdit : linkTextAdd;

	return (
		<div className="tribe-editor__ticket__attendee-registration">
			<LabelWithLink
				className="tribe-editor__ticket__attendee-registration-label-with-link"
				label={ label }
				linkDisabled={ isDisabled }
				linkHref={ attendeeRegistrationURL }
				linkText={ linkText }
			/>
			{ ! isCreated && (
				<span className="tribe-editor__ticket__attendee-registration-helper-text">
					{ helperText }
				</span>
			) }
		</div>
	);
};

AttendeesRegistration.propTypes = {
	attendeeRegistrationURL: PropTypes.string,
	hasAttendeeInfoFields: PropTypes.bool,
	isCreated: PropTypes.bool,
	isDisabled: PropTypes.bool,
};

export default AttendeesRegistration;
