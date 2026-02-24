/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Card } from '../../elements';

const RSVPDisabledBlock = () => (
	<Card className="tribe-common tribe-editor__tickets tribe-editor__inactive-block--rsvp">
		<div className="tribe-editor__rsvp__content-wrapper tickets-description">
			<h3 className="tribe-editor__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
				{ _x( 'RSVP is paused', 'RSVP disabled block title during migration', 'event-tickets' ) }
			</h3>
			<div className="tribe-editor__rsvp-disabled-text">
				{ _x( 'RSVP editing is disabled while a migration is in progress.', 'RSVP disabled block description during migration', 'event-tickets' ) }
			</div>
			<a
				className="tribe-editor__rsvp-disabled-text helper-link"
				href={ `${ window.ajaxurl?.replace( '/admin-ajax.php', '' ) || '/wp-admin' }/admin.php?page=tec-tickets-settings&tab=migrations` }
				target="_blank"
				rel="noopener noreferrer"
			>
				{ _x( 'Check migration status', 'Link to Event Tickets migrations tab', 'event-tickets' ) }
			</a>
		</div>
	</Card>
);

export default RSVPDisabledBlock;
