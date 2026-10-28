/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Card, Notice } from '../../../elements';

/**
 * Renders the not-supported message for RSVP on recurring events.
 *
 * @since 5.20.0
 * @since 5.30.0 Matches the disabled state of the Tickets block.
 * @since TBD Removed the link to the planned features article.
 * @return {Node} The not-supported message.
 */
export const renderBlockNotSupported = () => (
	<Card
		className="tribe-editor__not-supported-message tribe-editor__rsvp-not-supported"
		header={ __( 'RSVP', 'event-tickets' ) }
	>
		<div className="tribe-editor__title__help-messages">
			<Notice
				description={
					<p>{ __( 'RSVPs are not supported on recurring events.', 'event-tickets' ) }</p>
				}
			/>
		</div>
	</Card>
);
