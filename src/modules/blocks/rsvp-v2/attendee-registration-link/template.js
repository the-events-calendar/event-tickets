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
import { AttendeesRegistration as ARElement } from '../../../elements';
import './style.pcss';

const RSVPAttendeeRegistrationLink = ( {
	attendeeRegistrationURL,
	hasAttendeeInfoFields,
	isCreated,
	isDisabled,
	isModalOpen,
	onClick,
	onClose,
	onIframeLoad,
} ) => {
	const linkText = hasAttendeeInfoFields
		? __( 'Edit attendee information', 'event-tickets' )
		: __( '+ Collect attendee information', 'event-tickets' );

	return (
		<ARElement
			helperText={ __( 'Save your RSVP to enable attendee information fields', 'event-tickets' ) }
			iframeURL={ attendeeRegistrationURL }
			isDisabled={ isDisabled }
			isModalOpen={ isModalOpen }
			label=""
			linkText={ linkText }
			modalTitle={ __( 'Attendee Information', 'event-tickets' ) }
			onClick={ onClick }
			onClose={ onClose }
			onIframeLoad={ onIframeLoad }
			showHelperText={ ! isCreated }
			shouldCloseOnClickOutside={ false }
		/>
	);
};

RSVPAttendeeRegistrationLink.propTypes = {
	attendeeRegistrationURL: PropTypes.string.isRequired,
	hasAttendeeInfoFields: PropTypes.bool.isRequired,
	isCreated: PropTypes.bool.isRequired,
	isDisabled: PropTypes.bool.isRequired,
	isModalOpen: PropTypes.bool.isRequired,
	onClick: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	onIframeLoad: PropTypes.func.isRequired,
};

export default RSVPAttendeeRegistrationLink;
