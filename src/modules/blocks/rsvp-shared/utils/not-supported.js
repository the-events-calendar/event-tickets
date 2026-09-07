/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Renders the not-supported message for RSVP on recurring events.
 *
 * @since 5.20.0
 * @param {string} clientId The client ID of the block.
 * @return {Node} The not-supported message.
 */
export const renderBlockNotSupported = ( clientId ) => (
	<div className="tribe-editor__not-supported-message">
		<p className="tribe-editor__not-supported-message-text">
			{ __( 'RSVPs are not yet supported on recurring events.', 'event-tickets' ) }
			<br />
			<a
				className="tribe-editor__not-supported-message-link"
				href="https://evnt.is/1b7a"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Read about our plans for future features.', 'event-tickets' ) }
			</a>
			<br />
			<Button
				variant="secondary"
				onClick={ () => wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId ) }
			>
				{ __( 'Remove block', 'event-tickets' ) }
			</Button>
		</p>
	</div>
);
