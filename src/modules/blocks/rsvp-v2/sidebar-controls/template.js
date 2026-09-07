/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React from 'react';

/**
 * WordPress dependencies
 */
import { PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

const RSVPSidebarControls = ( {
	hasTicketsPlus,
	isLoading,
	notGoingResponses,
	onToggleNotGoing,
	onToggleShowAttendees,
	showAttendees,
} ) => (
	<InspectorControls>
		<PanelBody title={ __( 'RSVP Settings', 'event-tickets' ) }>
			<ToggleControl
				label={ __( 'Enable "Can\'t go" responses', 'event-tickets' ) }
				checked={ !! notGoingResponses }
				disabled={ isLoading }
				onChange={ onToggleNotGoing }
				__nextHasNoMarginBottom={ true }
			/>
			{ hasTicketsPlus && (
				<ToggleControl
					label={ __( 'Show attendees list on Event page', 'event-tickets' ) }
					help={ __( 'Attendees will have the ability to opt out of display.', 'event-tickets' ) }
					checked={ !! showAttendees }
					disabled={ isLoading }
					onChange={ onToggleShowAttendees }
					__nextHasNoMarginBottom={ true }
				/>
			) }
		</PanelBody>
	</InspectorControls>
);

RSVPSidebarControls.propTypes = {
	hasTicketsPlus: PropTypes.bool,
	isLoading: PropTypes.bool,
	notGoingResponses: PropTypes.bool,
	onToggleNotGoing: PropTypes.func.isRequired,
	onToggleShowAttendees: PropTypes.func.isRequired,
	showAttendees: PropTypes.bool,
};

export default RSVPSidebarControls;
