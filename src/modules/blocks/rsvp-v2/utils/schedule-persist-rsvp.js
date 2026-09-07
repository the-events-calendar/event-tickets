/**
 * Internal dependencies
 */
import { thunks } from '../../../data/blocks/rsvp-v2';

let timerId = null;

/**
 * Debounces RSVP persistence so rapid field edits coalesce into one request.
 *
 * @param {Function} dispatch  Redux dispatch.
 * @param {Object}   overrides Optional field overrides for the payload.
 * @param {number}   delay    Debounce delay in milliseconds.
 */
export const schedulePersistRSVP = ( dispatch, overrides = {}, delay = 500 ) => {
	clearTimeout( timerId );
	timerId = setTimeout( () => {
		dispatch( thunks.persistRSVP( overrides ) );
	}, delay );
};

/**
 * Clears any pending debounced persist call.
 */
export const cancelScheduledPersistRSVP = () => {
	clearTimeout( timerId );
	timerId = null;
};
