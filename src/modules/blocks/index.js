/**
 * Wordpress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

const { applyFilters, doAction } = wp.hooks;

/**
 * Internal dependencies
 */
import { initStore } from '@moderntribe/tickets/data';
import rsvp from '@moderntribe/tickets/blocks/rsvp';
import attendees from '@moderntribe/tickets/blocks/attendees';

let blocks = [
	rsvp,
	attendees,
];

/**
 * Allows filtering the list of blocks registered by Event Tickets.
 *
 * the
 *
 * @since TBD
 *
 * @param {Object[]} blocks The blocks that will be registered.
 */
blocks = applyFilters ( 'tec_tickets_blocks_registration_before', blocks );

blocks.forEach ( ( block ) => registerBlockType ( `tribe/${ block.id }`, block ) );

/**
 * Fires an action after Event Tickets blocks are registered.
 *
 * @since TBD
 *
 * @param {Object[]} blocks The blocks that were registered.
 */
doAction ( 'tec_tickets_blocks_registration_after', blocks );

// Initialize AFTER blocks are registered
// to avoid plugin shown as available in reducer
// but not having block available for use
initStore();

export default blocks;
