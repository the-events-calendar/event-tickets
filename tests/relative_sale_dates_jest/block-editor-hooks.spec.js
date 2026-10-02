import React from 'react';
import { applyFilters, doAction } from '@wordpress/hooks';
import { dispatch, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';
import SalePriceWindow from '@tec/tickets/relative-sale-dates/block-editor/sale-price-window';
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
const SALE_PRICE_BODY_FIELD = 'ticket[sale_price][relative]';
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

const relative = ( value, unit ) => ( { mode: 'relative', value, unit, anchor: 'start' } );
const salePriceBoundary = ( value, unit ) => ( { mode: 'relative', value, unit } );

const storedSalePriceRule = {
	start: { mode: 'now' },
	end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
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

describe( 'the Relative Sale Dates block editor hooks', () => {
	describe( 'on tec.tickets.blocks.fetchTicket', () => {
		it( 'should load the rule of the fetched ticket', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { id: 23, relative_sale_dates: storedRule }, {} );

			expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( storedRule );
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( storedRule );
		} );

		it( 'should load a fetched ticket without a rule as having none', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { id: 23, relative_sale_dates: null }, {} );

			expect( select( STORE_NAME ).getSavedRule( clientId ) ).toBeNull();
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeNull();
		} );

		it( 'should load the sale price rule of the fetched ticket', () => {
			const clientId = newClientId();
			const ticket = { id: 23, sale_price_data: { enabled: true, relative: storedSalePriceRule } };

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, ticket, {} );

			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( storedSalePriceRule );
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( storedSalePriceRule );
		} );

		it( 'should load a fetched sale price without a rule as having none', () => {
			const clientId = newClientId();
			const ticket = { id: 23, sale_price_data: { enabled: true, relative: null } };

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, ticket, {} );

			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toBeNull();
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toBeNull();
		} );

		it( 'should know no sale price rule for a fetched ticket without a sale price', () => {
			const clientId = newClientId();
			const ticket = { id: 23, sale_price_data: { enabled: false, relative: null } };

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, ticket, {} );

			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toBeUndefined();
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toBeUndefined();
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

		describe( 'with the event dates in the editor', () => {
			beforeEach( () => {
				window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
				setBlockEditorData();
				setEventState( DEFAULT_EVENT );
			} );

			afterEach( () => {
				delete window.tribe;
				clearBlockEditorGlobals();
			} );

			it( 'should keep the stored rule, by sending none, while the draft ends before it starts', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setRule( clientId, storedRule );
				dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );

				const body = buildBody( clientId );

				expect( body.has( BODY_FIELD ) ).toBe( false );
				expect( body.get( 'ticket[start_date]' ) ).toBe( '2026-10-01' );
			} );

			it( 'should send a draft that starts before it ends', () => {
				const clientId = newClientId();
				const draft = { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) };
				dispatch( STORE_NAME ).setDraftRule( clientId, draft );

				expect( JSON.parse( buildBody( clientId ).get( BODY_FIELD ) ) ).toStrictEqual( draft );
			} );
		} );

		describe( 'with a sale price', () => {
			/**
			 * Checks or unchecks the sale price of a ticket block in the legacy store, whose form holds the dates the
			 * editor always gives it: 2040-09-01 at 10:00 to the event start.
			 *
			 * @param {string}  clientId The client ID of the ticket block.
			 * @param {boolean} checked  Whether the sale price is checked.
			 *
			 * @return {void}
			 */
			function setSalePriceChecked( clientId, checked ) {
				const store = window.__tribe_common_store__;
				setTicketFormDates( store, clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );
				store.dispatch( legacyActions.setTempSalePriceChecked( clientId, checked ) );
			}

			beforeEach( () => {
				window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
				setBlockEditorData();
				setEventState( DEFAULT_EVENT );
			} );

			afterEach( () => {
				delete window.tribe;
				clearBlockEditorGlobals();
			} );

			it( 'should send the sale price draft as JSON, with the value and unit as integers', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, {
					start: { mode: 'relative', value: '3', unit: String( UNIT_WEEKS ) },
					end: { mode: 'relative', value: '10', unit: String( UNIT_DAYS ) },
				} );

				const sent = JSON.parse( buildBody( clientId ).get( SALE_PRICE_BODY_FIELD ) );

				expect( sent ).toStrictEqual( {
					start: { mode: 'relative', value: 3, unit: UNIT_WEEKS },
					end: { mode: 'relative', value: 10, unit: UNIT_DAYS },
				} );
			} );

			it( 'should send only the mode of a sale price boundary that is not relative', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, {
					start: { mode: 'now', value: 2, unit: UNIT_WEEKS },
					end: { mode: 'specific', value: 1, unit: UNIT_WEEKS },
				} );

				const sent = JSON.parse( buildBody( clientId ).get( SALE_PRICE_BODY_FIELD ) );

				expect( sent ).toStrictEqual( { start: { mode: 'now' }, end: { mode: 'specific' } } );
			} );

			it( 'should send the sales window and sale price rules side by side', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );
				dispatch( STORE_NAME ).setDraftRule( clientId, storedRule );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, storedSalePriceRule );

				const body = buildBody( clientId );

				expect( JSON.parse( body.get( BODY_FIELD ) ) ).toStrictEqual( storedRule );
				expect( JSON.parse( body.get( SALE_PRICE_BODY_FIELD ) ) ).toStrictEqual( storedSalePriceRule );
			} );

			it( 'should send no sale price rule while the sale price is unchecked', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, false );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, storedSalePriceRule );

				expect( buildBody( clientId ).has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
			} );

			it( 'should send no sale price rule for a sale price saved without one', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );
				dispatch( STORE_NAME ).setSalePriceRule( clientId, null );

				expect( buildBody( clientId ).has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
			} );

			describe( 'a draft the server would reject', () => {
				/**
				 * Builds the body of a ticket with a checked sale price.
				 *
				 * @param {Object} salesWindowRule The sales window draft rule.
				 * @param {Object} salePriceRule   The sale price draft rule.
				 *
				 * @return {FormData} The request body.
				 */
				function buildSalePriceBody( salesWindowRule, salePriceRule ) {
					const clientId = newClientId();
					setSalePriceChecked( clientId, true );
					dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );
					dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, salePriceRule );

					return buildBody( clientId );
				}

				it( 'should keep the stored sale price rule, by sending none, while the sale price ends before it starts', () => {
					const body = buildSalePriceBody(
						{ start: relative( 4, UNIT_WEEKS ), end: { mode: 'default' } },
						{ start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) }
					);

					expect( body.has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
					expect( body.has( BODY_FIELD ) ).toBe( true );
				} );

				it( 'should keep both stored rules, by sending neither, while the sales window ends before it starts', () => {
					const body = buildSalePriceBody(
						{ start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) },
						storedSalePriceRule
					);

					expect( body.has( BODY_FIELD ) ).toBe( false );
					expect( body.has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
				} );
			} );

			it( 'should send no sale price rule for a ticket the store knows no sale price rule of', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );

				expect( buildBody( clientId ).has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
			} );
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

	describe( 'on tec.tickets.blocks.Ticket.SalePrice.renderPickers', () => {
		const Row = () => null;
		const StartPicker = () => null;
		const EndPicker = () => null;

		/**
		 * Filters the sale price dates row of a ticket block for the given ticket provider.
		 *
		 * @param {string} provider The ticket provider the tickets block uses.
		 * @param {string} clientId The client ID of the ticket block.
		 *
		 * @return {Object} What the sale price section renders in place of its dates row.
		 */
		function filterSalePrice( provider, clientId ) {
			window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
			window.__tribe_common_store__ = { getState: () => ( { tickets: { blocks: { ticket: { provider } } } } ) };

			return applyFilters( 'tec.tickets.blocks.Ticket.SalePrice.renderPickers', <Row />, clientId, {
				start: <StartPicker />,
				end: <EndPicker />,
			} );
		}

		afterEach( () => {
			delete window.tribe;
			delete window.__tribe_common_store__;
		} );

		it( 'should render the sale price window options of a Tickets Commerce ticket with its pickers', () => {
			const clientId = newClientId();

			const rendered = filterSalePrice( TICKETS_COMMERCE, clientId );

			expect( rendered.type ).toBe( SalePriceWindow );
			expect( rendered.props.clientId ).toBe( clientId );
			expect( rendered.props.pickers.start.type ).toBe( StartPicker );
			expect( rendered.props.pickers.end.type ).toBe( EndPicker );
		} );

		it( 'should leave the dates row alone for a ticket another provider sells', () => {
			const rendered = filterSalePrice( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main', newClientId() );

			expect( rendered.type ).toBe( Row );
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

		it( 'should judge a start of Now by the date the ticket sends', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: { mode: 'default' }, end: relative( 1, UNIT_HOURS ) } );
			// Sales end at 18:00, an hour before the event; the ticket starts selling at 18:30.
			const state = legacyTicketState( clientId, '2040-10-20 18:30:00', '2040-10-20 19:00:00' );

			expect( isConfirmDisabled( clientId, false, state ) ).toBe( true );
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

		describe( 'with a sale price', () => {
			/*
			 * Sales open 4 weeks before the event, on 2040-09-22, and close when it starts on 2040-10-20; 1 week before
			 * the event is 2040-10-13, 2 weeks 2040-10-06 and 5 weeks 2040-09-15, and 2040-09-29 is a week into sales.
			 * Without a sales window rule, the ticket's own dates hold: 2040-09-01 at 10:00 to the event start.
			 */
			const salesWindowRule = { start: relative( 4, UNIT_WEEKS ), end: { mode: 'default' } };
			const TICKET_START = '2040-09-01 10:00:00';
			const TICKET_END = '2040-10-20 19:00:00';
			/**
			 * Builds the legacy ticket state of a ticket block with a sale price and the sale price dates its form holds.
			 *
			 * @param {string}      clientId The client ID of the ticket block.
			 * @param {boolean}     checked  Whether the sale price is checked.
			 * @param {string|null} start    The specific sale price start date, `YYYY-MM-DD`, or `null`.
			 *
			 * @return {Object} The legacy state.
			 */
			function salePriceState( clientId, checked, start = null ) {
				const store = window.__tribe_common_store__;
				legacyTicketState( clientId, TICKET_START, TICKET_END );
				store.dispatch( legacyActions.setTempSalePriceChecked( clientId, checked ) );
				store.dispatch( legacyActions.setTicketTempSaleStartDate( clientId, start ?? '' ) );

				return store.getState();
			}

			/**
			 * Asks whether the Create or Update button of a ticket block with a sale price rule is disabled.
			 *
			 * @param {Object}      salePriceRule       The sale price draft rule.
			 * @param {Object}      options             The sale price state.
			 * @param {boolean}     options.checked     Whether the sale price is checked.
			 * @param {string|null} options.start       The specific sale price start date the form holds.
			 * @param {Object|null} options.windowRule  The sales window draft rule.
			 *
			 * @return {boolean} Whether the button is disabled.
			 */
			function isDisabledWith(
				salePriceRule,
				{ checked = true, start = null, windowRule = salesWindowRule } = {}
			) {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, windowRule );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, salePriceRule );

				return isConfirmDisabled( clientId, false, salePriceState( clientId, checked, start ) );
			}

			it( 'should leave the button alone for a sale price inside the sales window that ends after it starts', () => {
				const rule = { start: salePriceBoundary( 2, UNIT_WEEKS ), end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( false );
			} );

			it( 'should disable Create and Update while the sale price ends before it starts', () => {
				const rule = { start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( true );
			} );

			it( 'should disable Create and Update while a relative sale price start falls before the sales window', () => {
				const rule = { start: salePriceBoundary( 5, UNIT_WEEKS ), end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( true );
			} );

			it( 'should disable Create and Update while a specific sale price start falls before the sales window', () => {
				const rule = { start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { start: '2040-09-01' } ) ).toBe( true );
				expect( isDisabledWith( rule, { start: '2040-09-29' } ) ).toBe( false );
			} );

			it( 'should disable Create and Update while a sale price number is cleared', () => {
				const rule = { start: salePriceBoundary( 2, UNIT_WEEKS ), end: salePriceBoundary( '', UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( true );
			} );

			it( 'should judge a Now start by the day sales open, never as outside the sales window', () => {
				expect( isDisabledWith( { start: { mode: 'now' }, end: salePriceBoundary( 1, UNIT_WEEKS ) } ) ).toBe( false );
				expect( isDisabledWith( { start: { mode: 'now' }, end: salePriceBoundary( 5, UNIT_WEEKS ) } ) ).toBe( true );
			} );

			it( 'should leave the button alone for a sale price the save drops for not being lower than the price', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, {
					start: salePriceBoundary( 1, UNIT_WEEKS ),
					end: salePriceBoundary( 2, UNIT_WEEKS ),
				} );
				const store = window.__tribe_common_store__;
				salePriceState( clientId, true );
				store.dispatch( legacyActions.setTicketTempPrice( clientId, '20.00' ) );

				store.dispatch( legacyActions.setTempSalePrice( clientId, '20.00' ) );
				expect( isConfirmDisabled( clientId, false, store.getState() ) ).toBe( false );

				store.dispatch( legacyActions.setTempSalePrice( clientId, '15.00' ) );
				expect( isConfirmDisabled( clientId, false, store.getState() ) ).toBe( true );
			} );

			it( 'should leave the button alone while the sale price is unchecked', () => {
				const rule = { start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { checked: false } ) ).toBe( false );
			} );

			it( 'should judge the sale price against the ticket\'s own dates without a sales window rule', () => {
				const rule = { start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { windowRule: null, start: '2040-08-31' } ) ).toBe( true );
				expect( isDisabledWith( rule, { windowRule: null, start: '2040-09-02' } ) ).toBe( false );
			} );

			it( 'should judge the sale price dates of the state the dashboard passes', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );
				dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, {
					start: { mode: 'specific' },
					end: salePriceBoundary( 1, UNIT_WEEKS ),
				} );
				const state = salePriceState( clientId, true, '2040-09-29' );

				window.__tribe_common_store__.dispatch( legacyActions.setTicketTempSaleStartDate( clientId, '2040-09-01' ) );

				expect( isConfirmDisabled( clientId, false, state ) ).toBe( false );
			} );

			it( 'should leave the button alone for a sale price saved without a rule, or not yet given one', () => {
				expect( isDisabledWith( null ) ).toBe( false );
				expect( isDisabledWith( undefined ) ).toBe( false );
			} );

			it( 'should leave the button alone for a sale price window of a ticket another provider sells', () => {
				window.__tribe_common_store__.dispatch(
					legacyActions.setTicketsProvider( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' )
				);
				const rule = { start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( false );
			} );

			it( 'should leave the sale price unjudged while the event dates cannot be read', () => {
				delete window.tec.events;
				const rule = { start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( false );
			} );
		} );
	} );

	describe( 'on tec.tickets.blocks.syncSaleEndWithEventStart', () => {
		const followsEventStart = ( clientId, value = true ) =>
			applyFilters( 'tec.tickets.blocks.syncSaleEndWithEventStart', value, clientId );

		it( 'should keep a relative sale end off the event start', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );

			expect( followsEventStart( clientId ) ).toBe( false );
		} );

		it( 'should keep a specific sale end off the event start', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, { start: storedRule.start, end: { mode: 'specific' } } );

			expect( followsEventStart( clientId ) ).toBe( false );
		} );

		it( 'should let a sale end that ends when the event starts follow it', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: { mode: 'default' }, end: { mode: 'default' } } );

			expect( followsEventStart( clientId ) ).toBe( true );
		} );

		it( 'should let the sale end of a ticket without a rule follow the event start', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, null );

			expect( followsEventStart( clientId ) ).toBe( true );
			expect( followsEventStart( clientId, false ) ).toBe( false );
			expect( followsEventStart( newClientId() ) ).toBe( true );
		} );
	} );

	describe.each( [ 'tec.tickets.blocks.ticketCreated', 'tec.tickets.blocks.ticketUpdated' ] )( 'on %s', ( hook ) => {
		it( 'should keep the draft rule as the saved one', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, storedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

			doAction( hook, clientId, 23, {} );

			expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( editedRule );
		} );
	} );
} );
