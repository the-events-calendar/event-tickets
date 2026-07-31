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

const defaultLinkTextAdd = __( '+ Add', 'event-tickets' );
const defaultLinkTextEdit = __( 'Edit', 'event-tickets' );
const defaultLabel = __( 'Attendee Information', 'event-tickets' );

const RSVPAttendeeRegistration = ( {
	attendeeRegistrationURL,
	hasAttendeeInfoFields,
	isCreated,
	isDisabled,
	isModalOpen,
	onClick,
	onClose,
	onIframeLoad,
	label = defaultLabel,
	linkTextAdd = defaultLinkTextAdd,
	linkTextEdit = defaultLinkTextEdit,
} ) => {
	const linkText = hasAttendeeInfoFields ? linkTextEdit : linkTextAdd;

	return (
		<ARElement
			helperText={ __( 'Save your RSVP to enable attendee information fields', 'event-tickets' ) }
			iframeURL={ attendeeRegistrationURL }
			isDisabled={ isDisabled }
			isModalOpen={ isModalOpen }
			label={ label }
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

RSVPAttendeeRegistration.propTypes = {
	attendeeRegistrationURL: PropTypes.string.isRequired,
	hasAttendeeInfoFields: PropTypes.bool.isRequired,
	isCreated: PropTypes.bool.isRequired,
	isDisabled: PropTypes.bool.isRequired,
	isModalOpen: PropTypes.bool.isRequired,
	onClick: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	onIframeLoad: PropTypes.func.isRequired,
	label: PropTypes.string,
	linkTextAdd: PropTypes.string,
	linkTextEdit: PropTypes.string,
};

export default RSVPAttendeeRegistration;
