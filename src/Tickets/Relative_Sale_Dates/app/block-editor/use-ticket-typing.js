/**
 * Whether the admin is typing in a window field of a ticket block.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createTypingTracker } from '../live-error';

/**
 * @typedef {Object} TicketTyping
 *
 * @property {function(function(): void): function(): void} subscribe Subscribes to the start and end of the typing.
 * @property {function(): boolean}                          isTyping  Whether the admin is typing.
 * @property {function(): void}                             type      Called on each keystroke.
 * @property {function(): void}                             settle    Called once the edit is done, as on an option picked.
 */

/**
 * The typing of each ticket block, by client ID, shared by every window of the ticket.
 *
 * @since TBD
 *
 * @type {Map<string, TicketTyping>}
 */
const typings = new Map();

/**
 * Returns the typing of a ticket block.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {TicketTyping} The typing.
 */
function getTicketTyping( clientId ) {
	if ( ! typings.has( clientId ) ) {
		const listeners = new Set();
		const notify = () => listeners.forEach( ( listener ) => listener() );
		const tracker = createTypingTracker( notify );

		typings.set( clientId, {
			subscribe( listener ) {
				listeners.add( listener );

				return () => {
					listeners.delete( listener );

					// The ticket's last window unmounted: nothing reads its typing, and a pending settle must not fire.
					if ( ! listeners.size ) {
						tracker.settle();
						typings.delete( clientId );
					}
				};
			},
			isTyping: tracker.isTyping,
			type() {
				const wasTyping = tracker.isTyping();

				tracker.type();

				if ( ! wasTyping ) {
					notify();
				}
			},
			settle() {
				if ( tracker.isTyping() ) {
					tracker.settle();
					notify();
				}
			},
		} );
	}

	return typings.get( clientId );
}

/**
 * Returns whether the admin is typing in a window field of a ticket block, and the calls that tell it.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{isTyping: boolean, type: function(): void, settle: function(): void}} Whether the admin is typing, and the
 *         calls for a keystroke and for an edit that is done.
 */
export function useTicketTyping( clientId ) {
	const typing = getTicketTyping( clientId );
	const isTyping = useSyncExternalStore( typing.subscribe, typing.isTyping );

	return { isTyping, type: typing.type, settle: typing.settle };
}
