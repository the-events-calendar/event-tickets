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
import { ActionButton } from '../../../../../../../modules/elements';
import { Attendees } from '../../../../../../../modules/icons';

const AttendeesActionButton = ( { href, canCreateTickets } ) =>
	canCreateTickets && (
		<ActionButton asLink={ true } href={ href } icon={ <Attendees /> } target="_blank">
			{ __( 'Attendees', 'event-tickets' ) }
		</ActionButton>
	);

AttendeesActionButton.propTypes = {
	href: PropTypes.string.isRequired,
	canCreateTickets: PropTypes.bool,
};

export default AttendeesActionButton;
