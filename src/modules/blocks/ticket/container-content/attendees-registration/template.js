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
import { LabelWithModal } from '@moderntribe/common/elements';
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

	const iFrame = (
		<iframe
			className="tribe-editor__ticket__attendee-registration-modal-iframe"
			src={ attendeeRegistrationURL }
		>
		</iframe>
	);

	return (
		<div className="tribe-editor__ticket__attendee-registration">
			<LabelWithModal
				className="tribe-editor__ticket__attendee-registration-label-with-modal"
				label={ label }
				modalButtonDisabled={ isDisabled }
				modalButtonLabel={ linkText }
				modalClassName="tribe-editor__ticket__attendee-registration-modal"
				modalContent={ iFrame }
				modalTitle={ label }
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
