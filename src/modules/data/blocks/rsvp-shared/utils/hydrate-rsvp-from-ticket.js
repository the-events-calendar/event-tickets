/**
 * Internal dependencies
 */
import { normalizeRSVPResponseFromV2Ticket } from './normalize-rsvp-response';
import {
	getAttendanceCountsFromV2Ticket,
	hydrateRsvpAttendanceCounts,
} from './hydrate-rsvp-attendance-counts';
import { dispatchRsvpDetails, dispatchRsvpTempDetails } from './dispatch-rsvp-details';

/**
 * Hydrates the RSVP Redux store from a V2 TEC REST ticket payload.
 *
 * @param {Function} dispatch Redux dispatch.
 * @param {Object}   actions  RSVP action creators.
 * @param {Object}   ticket   Ticket REST response object.
 * @param {Object}   options  Normalization options passed to normalizeRSVPResponseFromV2Ticket.
 * @return {boolean} Whether the ticket was hydrated.
 */
export const hydrateRsvpFromTicket = ( dispatch, actions, ticket, options = {} ) => {
	if ( ! ticket?.id ) {
		return false;
	}

	const normalized = normalizeRSVPResponseFromV2Ticket( ticket, {
		title: 'RSVP',
		description: '',
		...options,
	} );

	dispatch( actions.createRSVP() );
	dispatch( actions.setRSVPId( normalized.id ) );
	dispatch( actions.setRSVPHasAttendeeInfoFields( normalized.hasAttendeeInfoFields ) );
	dispatchRsvpDetails( dispatch, actions, normalized.details );
	dispatchRsvpTempDetails( dispatch, actions, normalized.tempDetails );
	hydrateRsvpAttendanceCounts( dispatch, actions, getAttendanceCountsFromV2Ticket( ticket ) );

	return true;
};
