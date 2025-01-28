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
import { AttendeesRegistration as ARElement } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';

const linkTextAdd = __( '+ Add', 'event-tickets' );
const linkTextEdit = __( 'Edit', 'event-tickets' );

const noop = ()=>{};

const AttendeesRegistration = ( {
	attendeeRegistrationURL,
	hasAttendeeInfoFields = false,
	isCreated = false,
	isDisabled = false,
	isModalOpen = false,
	onClick = noop,
	onClose = noop,
	onIframeLoad = noop,
} ) => {
	const linkText = hasAttendeeInfoFields ? linkTextEdit : linkTextAdd;

	return (
		<ARElement
			// eslint-disable-next-line no-undef
			helperText={sprintf(
				/* Translators: %s - the singular, lowercase label for a ticket. */
				__(
					'Save your %s to enable attendee information fields',
					'event-tickets'
				),
				TICKET_LABELS.ticket.singularLowercase
			)}
			iframeURL={ attendeeRegistrationURL }
			isDisabled={ isDisabled }
			isModalOpen={ isModalOpen }
			label={ __( 'Attendee Information', 'event-tickets' ) }
			linkText={ linkText }
			modalTitle={ __( 'Attendee Information', 'event-tickets' ) }
			onClick={ onClick }
			onClose={ onClose }
			onIframeLoad={ onIframeLoad }
			showHelperText={ ! isCreated }
			// @todo: @paulmskim shouldCloseOnClickOutside is a fix until we can figure out modal closing issue in WP 5.5.
			shouldCloseOnClickOutside={ false }
		/>
	);
};

AttendeesRegistration.propTypes = {
	attendeeRegistrationURL: PropTypes.string.isRequired,
	hasAttendeeInfoFields: PropTypes.bool,
	isCreated: PropTypes.bool,
	isDisabled: PropTypes.bool,
	isModalOpen: PropTypes.bool,
	onClick: PropTypes.func,
	onClose: PropTypes.func,
	onIframeLoad: PropTypes.func,
};

export default AttendeesRegistration;
