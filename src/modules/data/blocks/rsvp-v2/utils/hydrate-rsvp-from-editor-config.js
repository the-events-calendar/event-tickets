/**
 * Internal dependencies
 */
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

	return hydrateRsvpFromTicket( dispatch, actions, ticket );
};
