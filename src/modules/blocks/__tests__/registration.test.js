/**
 * Tests for the Block API version the Event Tickets blocks register with.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { createHooks } from '@wordpress/hooks';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { BLOCK_API_VERSION } from '@moderntribe/tickets/blocks/with-block-wrapper';
import rsvp from '@moderntribe/tickets/blocks/rsvp';
import rsvpV2 from '@moderntribe/tickets/blocks/rsvp-v2';
import rsvpDisabled from '@moderntribe/tickets/blocks/rsvp-disabled';

jest.mock( '@wordpress/blocks', () => ( { registerBlockType: jest.fn() } ) );
/* The store and the Tickets block filters need a running editor; neither affects registration. */
jest.mock( '../../data', () => ( { initStore: jest.fn() } ) );
jest.mock( '../../data/blocks/rsvp-v2/tickets-block-filters', () => ( { initTicketsBlockFilters: jest.fn() } ) );

const thirdPartyBlock = {
	id: 'third-party-static',
	title: 'Third Party Static',
	category: 'tribe-tickets',
	edit: () => null,
	save: () => null,
};

/**
 * Loads the registration module with a fresh hooks instance and editor config.
 *
 * @param {Object} ticketsConfig The `tribe_editor_config.tickets` value to load with.
 *
 * @return {Object<string, Object>} The settings each block was registered with, keyed by block name.
 */
const register = ( ticketsConfig ) => {
	global.wp.hooks = createHooks();
	window.tribe_editor_config = { tickets: ticketsConfig };
	registerBlockType.mockClear();

	global.wp.hooks.addFilter( 'tec.tickets.blocks.beforeRegistration', 'test/third-party', ( blocks ) => [
		...blocks,
		thirdPartyBlock,
	] );

	jest.isolateModules( () => {
		require( '@moderntribe/tickets/blocks' );
	} );

	return Object.fromEntries( registerBlockType.mock.calls );
};

describe( 'Block registration', () => {
	afterEach( () => {
		delete window.tribe_editor_config;
	} );

	it.each( [
		[ 'RSVP V1', {}, rsvp ],
		[ 'RSVP V2', { rsvpV2: { enabled: true } }, rsvpV2 ],
		[ 'RSVP disabled', { rsvpDisabled: true }, rsvpDisabled ],
	] )( 'Should register the Event Tickets blocks at Block API version 3 with %s', ( name, config, rsvpBlock ) => {
		const registered = register( config );

		expect( registered[ 'tribe/rsvp' ] ).toMatchObject( {
			apiVersion: BLOCK_API_VERSION,
			attributes: rsvpBlock.attributes,
			supports: rsvpBlock.supports,
		} );
		expect( registered[ 'tribe/attendees' ].apiVersion ).toBe( BLOCK_API_VERSION );
	} );

	it( 'Should leave a block added through the registration filter at the version it declares', () => {
		const registered = register( {} );

		expect( registered[ `tribe/${ thirdPartyBlock.id }` ] ).toBe( thirdPartyBlock );
	} );
} );
