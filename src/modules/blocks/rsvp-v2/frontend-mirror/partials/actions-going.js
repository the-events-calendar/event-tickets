/**
 * Mirrors `src/views/v2/commerce/rsvp/actions/rsvp/going.php` (non-interactive in editor).
 *
 * The button is intentionally left enabled so it renders with the full accent
 * color, matching the frontend. The editor wrapper (`style.pcss`) blocks
 * interaction via `pointer-events: none`.
 */
import React from 'react';
import { _x } from '@wordpress/i18n';

const RSVPActionsGoing = () => (
	<div className="tribe-tickets__rsvp-actions-rsvp-going">
		<button
			className="tribe-common-c-btn tribe-tickets__rsvp-actions-button-going tribe-common-b1 tribe-common-b2--min-medium"
			type="button"
		>
			{ _x( 'Going', 'Label for the RSVP going button', 'event-tickets' ) }
		</button>
	</div>
);

export default RSVPActionsGoing;
