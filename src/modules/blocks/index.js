/**
 * Wordpress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

const { applyFilters, doAction } = wp.hooks;

/**
 * Internal dependencies
 */
import { initStore } from '../data';
import rsvp from './rsvp';
import attendees from './attendees';

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
