/**
 * Internal dependencies
 */
import { globals } from '@moderntribe/common/utils';
import { getInitialTicket } from '../config';
import { hydrateRsvpFromTicket } from '../../rsvp-shared/utils/hydrate-rsvp-from-ticket';

/**
 * Synchronously hydrates RSVP state from PHP-localized editor config.
 *
 * @param {Function} dispatch Redux dispatch.
 * @param {Object}   actions  RSVP action creators.
 * @return {boolean} Whether an initial ticket was hydrated.
 */
export const hydrateRsvpFromEditorConfig = ( dispatch, actions ) => {
	const ticket = getInitialTicket();
	const hydrated = hydrateRsvpFromTicket( dispatch, actions, ticket );

	// No existing RSVP ticket yet (e.g. a brand-new event): seed the global IAC
	// default so a newly created RSVP block reflects the site's default
	// attendee-collection choice instead of starting at 'none'.
	if ( ! hydrated ) {
		dispatch( actions.setRSVPIAC( globals.iacVars().iacDefault || 'none' ) );
	}

	return hydrated;
};
