/**
 * Mirrors `src/views/v2/commerce/rsvp/actions/rsvp/not-going.php` (non-interactive in editor).
 *
 * The button is intentionally left enabled so it renders with the full accent
 * color, matching the frontend. The editor wrapper (`style.pcss`) blocks
 * interaction via `pointer-events: none`.
 */
import React from 'react';
import { _x } from '@wordpress/i18n';

const RSVPActionsNotGoing = () => (
	<div className="tribe-tickets__rsvp-actions-rsvp-not-going">
		<button
			className="tribe-common-cta tribe-common-cta--alt tribe-tickets__rsvp-actions-button-not-going"
			type="button"
		>
			{ _x(
				"Can't go",
				'Label for the RSVP "can\'t go" version of the not going button',
				'event-tickets'
			) }
		</button>
	</div>
);

export default RSVPActionsNotGoing;
