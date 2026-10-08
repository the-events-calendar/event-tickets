import React from 'react';
import { addFilter, applyFilters, doAction, filters, removeFilter } from '@wordpress/hooks';
import { dispatch, getStoreState, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import {
	DEFAULT_EVENT,
	clearBlockEditorGlobals,
	setBlockEditorData,
	setEventState,
	setTicketFormDates,
} from './block-editor-event-state';
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
		 * Filters the Sale Duration picker of a ticket block for the given providers.
		 *
		 * @param {string} provider       The ticket provider the tickets block uses.
		 * @param {string} clientId       The client ID of the ticket block.
		 * @param {string} ticketProvider The provider slug the ticket keeps once fetched, or an empty string for a new one.
		 *
		 * @return {Object} What the Sale Duration section renders.
		 */
		function filterDuration( provider, clientId, ticketProvider = '' ) {
			const tickets = { allClientIds: [ clientId ], byClientId: { [ clientId ]: { provider: ticketProvider } } };
			window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
			window.__tribe_common_store__ = { getState: () => ( { tickets: { blocks: { ticket: { provider, tickets } } } } ) };

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

		// The server judges a ticket by its own provider, which an event's older ticket may not share with the block.
		it( 'should leave the picker alone for an older ticket another provider sells under a Tickets Commerce block', () => {
			const rendered = filterDuration( TICKETS_COMMERCE, newClientId(), 'woo' );

			expect( rendered.type ).toBe( Picker );
		} );

		it( 'should render the sales window options of an older Tickets Commerce ticket under another provider', () => {
			const rendered = filterDuration( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main', newClientId(), 'tc' );

			expect( rendered.type ).toBe( SalesWindow );
		} );
	} );

	describe( 'on tec.tickets.blocks.Ticket.SaleWindow.dates', () => {
		const ticketDates = { fromDate: 'September 1, 2040', toDate: 'December 31, 2040' };

		/**
		 * Filters the sale dates the ticket header shows for a ticket block.
		 *
		 * @param {string} clientId The client ID of the ticket block.
		 *
		 * @return {{fromDate: string, toDate: string}} The dates the header shows.
		 */
		function filterDates( clientId ) {
			return applyFilters( 'tec.tickets.blocks.Ticket.SaleWindow.dates', ticketDates, clientId );
		}

		beforeEach( () => {
			setBlockEditorData();
			setEventState( DEFAULT_EVENT );
		} );

		afterEach( () => {
			clearBlockEditorGlobals();
		} );

		it( 'should show the dates the rule of a ticket works out to', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: storedRule.start, end: { mode: 'default' } } );

			expect( filterDates( clientId ) ).toStrictEqual( { fromDate: 'October 6, 2040', toDate: 'October 20, 2040' } );
		} );

		it( 'should keep the ticket\'s own start when sales start now', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: { mode: 'default' }, end: storedRule.end } );

			expect( filterDates( clientId ) ).toStrictEqual( { fromDate: ticketDates.fromDate, toDate: 'October 20, 2040' } );
		} );

		it( 'should leave the dates of a ticket without a rule alone', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, null );

			expect( filterDates( clientId ) ).toStrictEqual( ticketDates );
			expect( filterDates( newClientId() ) ).toStrictEqual( ticketDates );
		} );

		it( 'should leave the dates alone while the event dates cannot be read', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, storedRule );
			delete window.tec.events;

			expect( filterDates( clientId ) ).toStrictEqual( ticketDates );
		} );
	} );

	describe( 'on tec.tickets.blocks.confirmButton.isDisabled', () => {
		/**
		 * Builds the legacy ticket state of a ticket block with the sale dates its form sends.
		 *
		 * @param {string} clientId The client ID of the ticket block.
		 * @param {string} start    The sale start the form sends, `YYYY-MM-DD HH:mm:ss`.
		 * @param {string} end      The sale end the form sends, `YYYY-MM-DD HH:mm:ss`.
		 *
		 * @return {Object} The legacy state.
		 */
		function legacyTicketState( clientId, start, end ) {
			setTicketFormDates( window.__tribe_common_store__, clientId, start, end );

			return window.__tribe_common_store__.getState();
		}

		/**
		 * Asks whether the Create or Update button of a ticket block is disabled.
		 *
		 * @param {string}  clientId   The client ID of the ticket block.
		 * @param {boolean} isDisabled Whether the legacy checks disable it.
		 * @param {Object}  state      The legacy state.
		 *
		 * @return {boolean} Whether the button is disabled.
		 */
		function isConfirmDisabled( clientId, isDisabled, state = legacyTicketState( clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' ) ) {
			return applyFilters( 'tec.tickets.blocks.confirmButton.isDisabled', isDisabled, state, { clientId } );
		}

		/**
		 * Builds the legacy state of a ticket block whose specific dates end before they start, so it has a duration
		 * error. Every other legacy check passes unless `hasChanges` is false.
		 *
		 * @param {string}  clientId   The client ID of the ticket block.
		 * @param {boolean} hasChanges Whether the ticket has changes to save.
		 *
		 * @return {Object} The legacy state.
		 */
		function durationErrorState( clientId, hasChanges = true ) {
			const store = window.__tribe_common_store__;
			legacyTicketState( clientId, '2040-10-21 10:00:00', '2040-10-20 19:00:00' );
			store.dispatch( legacyActions.setTicketTempTitle( clientId, 'Ticket' ) );
			store.dispatch( legacyActions.setTicketTempPrice( clientId, '10' ) );
			store.dispatch( legacyActions.setTicketTempCapacityType( clientId, 'unlimited' ) );
			store.dispatch( legacyActions.setTicketHasChanges( clientId, hasChanges ) );
			store.dispatch( legacyActions.setTicketHasDurationError( clientId, true ) );

			return store.getState();
		}

		const relative = ( value, unit ) => ( { mode: 'relative', value, unit, anchor: 'start' } );

		beforeEach( () => {
			window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
			setBlockEditorData();
			setEventState( DEFAULT_EVENT ).dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );
		} );

		afterEach( () => {
			delete window.tribe;
			clearBlockEditorGlobals();
		} );

		it( 'should disable Create and Update while the window ends before it starts', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );

			expect( isConfirmDisabled( clientId, false ) ).toBe( true );
		} );

		it( 'should leave the button alone for a window that starts before it ends', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) } );

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
			expect( isConfirmDisabled( clientId, true ) ).toBe( true );
		} );

		it( 'should disable Create and Update while a relative start or end has no number', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( '', UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) } );

			expect( isConfirmDisabled( clientId, false ) ).toBe( true );
		} );

		// The server sells a start of Now from now on when the ticket dates it later, as a ticket switched from a specific date.
		it( 'should judge a start of Now that the ticket dates later than now as now', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: { mode: 'default' }, end: relative( 1, UNIT_HOURS ) } );
			// Sales end at 18:00, an hour before the event; the hidden start, 18:30, is later than that but not than now.
			const state = legacyTicketState( clientId, '2040-10-20 18:30:00', '2040-10-20 19:00:00' );

			expect( isConfirmDisabled( clientId, false, state ) ).toBe( false );
		} );

		it( 'should run ahead of the filters of other extensions, so it never enables a button one of them disabled', () => {
			const hook = 'tec.tickets.blocks.confirmButton.isDisabled';
			const namespace = 'tec.tickets.relative-sale-dates';
			const { callback, priority } = filters[ hook ].handlers.find( ( handler ) => namespace === handler.namespace );
			// As Seating does for a seated ticket without a seat type, from a script that registers its filter first.
			removeFilter( hook, namespace );
			addFilter( hook, 'tests/seating', () => true );
			addFilter( hook, namespace, callback, priority );
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) } );

			const isDisabled = isConfirmDisabled( clientId, false, durationErrorState( clientId ) );
			removeFilter( hook, 'tests/seating' );

			expect( isDisabled ).toBe( true );
		} );

		it( 'should judge a rule that hides the specific dates by the rule, not by their legacy duration error', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) } );

			expect( isConfirmDisabled( clientId, true, durationErrorState( clientId ) ) ).toBe( false );
		} );

		it( 'should keep the button disabled for another reason the legacy checks give', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) } );

			expect( isConfirmDisabled( clientId, true, durationErrorState( clientId, false ) ) ).toBe( true );
		} );

		it( 'should leave the button alone for a ticket another provider sells', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );
			window.__tribe_common_store__.dispatch( legacyActions.setTicketsProvider( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' ) );

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
		} );

		it( 'should leave the button alone for an older ticket another provider sells under a Tickets Commerce block', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );
			window.__tribe_common_store__.dispatch( legacyActions.setTicketProvider( clientId, 'woo' ) );

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
		} );

		it( 'should leave the button alone for a ticket without a rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, null );

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
		} );

		it( 'should leave the button alone while the event dates cannot be read', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );
			delete window.tec.events;

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
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
