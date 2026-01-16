/**
 * Wordpress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

const { addFilter, applyFilters, doAction } = wp.hooks;

/**
 * Internal dependencies
 */
import { initStore } from '../data';
import rsvp from './rsvp';
import rsvpV2 from './rsvp-v2';
import attendees from './attendees';
import { isV2Enabled } from '../data/blocks/rsvp-v2/config';
import { initTicketsBlockFilters } from '../data/blocks/rsvp-v2/tickets-block-filters';

/**
 * Filter callback to swap V1 RSVP block with V2 when V2 is enabled.
 *
 * @since TBD
 * @param {Object[]} blocks The blocks to be registered.
 * @return {Object[]} The filtered blocks.
 */
const maybeSwapRsvpBlock = ( blocks ) => {
	if ( ! isV2Enabled() ) {
		return blocks;
	}

	// Replace V1 RSVP block with V2.
	return blocks.map( ( block ) => ( block.id === 'rsvp' ? rsvpV2 : block ) );
};

// Register the filter to swap RSVP blocks.
addFilter( 'tec.tickets.blocks.beforeRegistration', 'tec.tickets.rsvp-v2', maybeSwapRsvpBlock );

// Initialize filters to exclude RSVP V2 tickets from the Tickets block.
initTicketsBlockFilters();

let blocks = [ rsvp, attendees ];

/**
 * Allows filtering the list of blocks registered by Event Tickets.
 *
 * the
 *
 * @since 5.8.0
 * @param {Object[]} blocks The blocks that will be registered.
 */
blocks = applyFilters( 'tec.tickets.blocks.beforeRegistration', blocks );

blocks.forEach( ( block ) => registerBlockType( `tribe/${ block.id }`, block ) );

/**
 * Fires an action after Event Tickets blocks are registered.
 *
 * @since 5.8.0
 * @param {Object[]} blocks The blocks that were registered.
 */
doAction( 'tec.tickets.blocks.afterRegistration', blocks );

// Initialize AFTER blocks are registered
// to avoid plugin shown as available in reducer
// but not having block available for use
initStore();

export default blocks;
