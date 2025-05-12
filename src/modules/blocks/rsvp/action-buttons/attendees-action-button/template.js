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
import { ActionButton } from '../../../../elements';
import { Attendees } from '../../../../icons';

const AttendeesActionButton = ( { href, isDisabled } ) => (
	<ActionButton
		asLink={ true }
		className="tribe-editor__rsvp__action-button tribe-editor__rsvp__action-button--attendees"
		disabled={ isDisabled }
		id="attendees-rsvp"
		href={ href }
		icon={ <Attendees /> }
		target="_blank"
	>
		{ __( 'View Attendees', 'event-tickets' ) }
	</ActionButton>
);

AttendeesActionButton.propTypes = {
	href: PropTypes.string,
	isDisabled: PropTypes.bool,
};

export default AttendeesActionButton;
