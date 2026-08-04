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

const RSVPSidebarControls = ( { isLoading, notGoingResponses, onToggleNotGoing } ) => (
	<InspectorControls>
		<PanelBody title={ __( 'RSVP Settings', 'event-tickets' ) }>
			<ToggleControl
				label={ __( 'Enable "Can\'t go" responses', 'event-tickets' ) }
				checked={ !! notGoingResponses }
				disabled={ isLoading }
				onChange={ onToggleNotGoing }
				__nextHasNoMarginBottom={ true }
			/>
		</PanelBody>
	</InspectorControls>
);

RSVPSidebarControls.propTypes = {
	isLoading: PropTypes.bool,
	notGoingResponses: PropTypes.bool,
	onToggleNotGoing: PropTypes.func.isRequired,
};

export default RSVPSidebarControls;
