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
					<p>
						{ __( 'RSVPs are not yet supported on recurring events.', 'event-tickets' ) }{ ' ' }
						<a
							className="helper-link"
							href="https://evnt.is/1b7a"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Read about our plans for future features', 'event-tickets' ) }
						</a>
					</p>
				}
			/>
		</div>
	</Card>
);
