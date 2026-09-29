import React from 'react';
import { applyFilters, doAction } from '@wordpress/hooks';
import { dispatch, getStoreState, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import '@tec/tickets/relative-sale-dates/block-editor';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

// `@wordpress/components` is not installed: the block editor provides it at runtime.
jest.mock( '@wordpress/components', () => ( {} ) );

const TICKETS_COMMERCE = 'TEC\\Tickets\\Commerce\\Module';

const BODY_FIELD = 'ticket[relative_sale_dates]';
const UNIT_HOURS = 3600;
const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const storedRule = {
	start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
	end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'end' },
};

const editedRule = {
	start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'start' },
	end: { mode: 'default' },
};

let clientCount = 0;

/**
 * Returns a client ID no earlier spec has used: the store is registered once for the whole file.
 *
 * @return {string} The client ID.
 */
function newClientId() {
	clientCount++;

	return `ticket-block-${ clientCount }`;
}

/**
 * Builds the request body of a ticket save the way the ticket saga does, filters included.
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {FormData} The request body.
 */
function buildBody( clientId ) {
	const body = new FormData();
	body.append( 'ticket[start_date]', '2026-10-01' );

	return applyFilters( 'tec.tickets.blocks.setBodyDetails', body, clientId );
}


/**
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Object|null|undefined} The rule the store keeps as the ticket's saved one, which no selector exposes.
 */
function getSavedRule( clientId ) {
	return getStoreState( STORE_NAME )[ clientId ]?.saved;
}

describe( 'the Relative Sale Dates block editor hooks', () => {
	describe( 'on tec.tickets.blocks.fetchTicket', () => {
		it( 'should load the rule of the fetched ticket', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { id: 23, provider: 'tc', relative_sale_dates: storedRule }, {} );

			expect( getSavedRule( clientId ) ).toStrictEqual( storedRule );
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( storedRule );
		} );

		it( 'should load a fetched ticket without a rule as having none', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { id: 23, provider: 'tc', relative_sale_dates: null }, {} );

			expect( getSavedRule( clientId ) ).toBeNull();
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeNull();
		} );

		it( 'should leave a ticket another provider sells out of the store and its requests', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { id: 23, provider: 'woo', relative_sale_dates: null }, {} );

			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeUndefined();
			expect( buildBody( clientId ).has( BODY_FIELD ) ).toBe( false );
		} );
	} );

	describe( 'on tec.tickets.blocks.setBodyDetails', () => {
		it( 'should send the draft rule as JSON', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

			const body = buildBody( clientId );

			expect( JSON.parse( body.get( BODY_FIELD ) ) ).toStrictEqual( editedRule );
			expect( body.get( 'ticket[start_date]' ) ).toBe( '2026-10-01' );
		} );

		it( 'should send the value and unit of a relative end as integers', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, {
				start: { mode: 'relative', value: '3', unit: String( UNIT_DAYS ), anchor: 'start' },
				end: { mode: 'relative', value: '1', unit: String( UNIT_HOURS ), anchor: 'end' },
			} );

			const sent = JSON.parse( buildBody( clientId ).get( BODY_FIELD ) );

			expect( sent ).toStrictEqual( {
				start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'start' },
				end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'end' },
			} );
		} );

		it( 'should send only the mode of an end that is not relative', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, {
				start: { mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
				end: { mode: 'specific', value: 1, unit: UNIT_HOURS, anchor: 'start' },
			} );

			const sent = JSON.parse( buildBody( clientId ).get( BODY_FIELD ) );

			expect( sent ).toStrictEqual( { start: { mode: 'default' }, end: { mode: 'specific' } } );
		} );

		it( 'should send an empty rule, which removes the stored one, when the draft has none', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, null );

			expect( buildBody( clientId ).get( BODY_FIELD ) ).toBe( '' );
		} );

		it( 'should send no rule for a ticket the store knows nothing of', () => {
			const body = buildBody( newClientId() );

			expect( body.has( BODY_FIELD ) ).toBe( false );
		} );
	} );

	describe( 'on tec.tickets.blocks.ticketCancelled', () => {
		it( 'should restore the saved rule into the draft', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

			doAction( 'tec.tickets.blocks.ticketCancelled', clientId );

			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( storedRule );
		} );
	} );

	describe( 'on tec.tickets.blocks.Ticket.Duration.renderPicker', () => {
		const Picker = () => null;

		/**
		 * Filters the Sale Duration picker of a ticket block for the given ticket provider.
		 *
		 * @param {string} provider The ticket provider the tickets block uses.
		 * @param {string} clientId The client ID of the ticket block.
		 *
		 * @return {Object} What the Sale Duration section renders.
		 */
		function filterDuration( provider, clientId ) {
			window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
			window.__tribe_common_store__ = { getState: () => ( { tickets: { blocks: { ticket: { provider } } } } ) };

			return applyFilters( 'tec.tickets.blocks.Ticket.Duration.renderPicker', <Picker />, clientId );
		}

		afterEach( () => {
			delete window.tribe;
			delete window.__tribe_common_store__;
		} );

		it( 'should render the sales window options of a Tickets Commerce ticket around the picker', () => {
			const clientId = newClientId();

			const rendered = filterDuration( TICKETS_COMMERCE, clientId );

			expect( rendered.type ).toBe( SalesWindow );
			expect( rendered.props.clientId ).toBe( clientId );
			expect( rendered.props.picker.type ).toBe( Picker );
		} );

		it( 'should leave the picker alone for a ticket another provider sells', () => {
			const rendered = filterDuration( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main', newClientId() );

			expect( rendered.type ).toBe( Picker );
		} );
	} );

	describe.each( [ 'tec.tickets.blocks.ticketCreated', 'tec.tickets.blocks.ticketUpdated' ] )( 'on %s', ( hook ) => {
		it( 'should keep the draft rule as the saved one', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

			doAction( hook, clientId, 23, {} );

			expect( getSavedRule( clientId ) ).toStrictEqual( editedRule );
		} );
	} );
} );
