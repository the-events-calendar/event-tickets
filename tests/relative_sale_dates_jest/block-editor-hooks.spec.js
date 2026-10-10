import React from 'react';
import { addFilter, applyFilters, doAction, filters, removeFilter } from '@wordpress/hooks';
import { dispatch, getStoreState, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import SalesWindow from '@tec/tickets/relative-sale-dates/block-editor/sales-window';
import SalePriceWindow from '@tec/tickets/relative-sale-dates/block-editor/sale-price-window';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
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
const WOO_PROVIDER = 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main';

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

/*
 * Sales open 4 weeks before the event, on 2040-09-22, and close when it starts on 2040-10-20; 1 week before the event
 * is 2040-10-13, 2 weeks 2040-10-06 and 5 weeks 2040-09-15, and 2040-09-29 is a week into sales.
 */
const salesWindowRule = { start: relative( 4, UNIT_WEEKS ), end: { mode: 'default' } };

const storedSalePrice = {
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

/**
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} kind     The window kind.
 *
 * @return {Object|null|undefined} The rule the store keeps as the ticket's saved one, which no selector exposes.
 */
function getSavedRule( clientId, kind = SALES_WINDOW ) {
	return getStoreState( STORE_NAME )[ clientId ]?.[ kind.id ]?.saved;
}

/**
 * Checks or unchecks the sale price of a ticket block of a Tickets Commerce block in the legacy store, with the event
 * dates in the editor, whose form holds the dates the editor always gives it: 2040-09-01 at 10:00 to the event start.
 *
 * @param {string}  clientId The client ID of the ticket block.
 * @param {boolean} checked  Whether the sale price is checked.
 *
 * @return {void}
 */
function setSalePriceChecked( clientId, checked ) {
	setBlockEditorData();
	const store = setEventState( DEFAULT_EVENT );
	store.dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );

	setTicketFormDates( store, clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );
	store.dispatch( legacyActions.setTempSalePriceChecked( clientId, checked ) );
}

/**
 * Makes a new ticket block with a checked sale price, whose sales window opens 4 weeks before the event.
 *
 * @return {string} The client ID of the ticket block.
 */
function newSalePriceTicket() {
	const clientId = newClientId();
	setSalePriceChecked( clientId, true );
	dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );

	return clientId;
}

/**
 * Makes a new ticket block whose form sends the dates the editor always gives it: 2040-09-01 at 10:00 to the event
 * start.
 *
 * @return {string} The client ID of the ticket block.
 */
function newSalesWindowTicket() {
	const clientId = newClientId();
	setTicketFormDates( window.__tribe_common_store__, clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );

	return clientId;
}

/**
 * How each window's rule travels in the requests of a ticket block.
 *
 * @type {Object[]}
 */
const REQUEST_CASES = [
	{
		title: 'the sales window',
		kind: SALES_WINDOW,
		field: BODY_FIELD,
		newTicket: newClientId,
		fetched: ( rule ) => ( { id: 23, provider: 'tc', relative_sale_dates: rule } ),
		stored: storedRule,
		edited: editedRule,
		later: { start: relative( 4, UNIT_DAYS ), end: { mode: 'default' } },
		typed: {
			start: { mode: 'relative', value: '3', unit: String( UNIT_DAYS ), anchor: 'start' },
			end: { mode: 'relative', value: '1', unit: String( UNIT_HOURS ), anchor: 'end' },
		},
		sentTyped: {
			start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'start' },
			end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'end' },
		},
		notRelative: {
			start: { mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'specific', value: 1, unit: UNIT_HOURS, anchor: 'start' },
		},
		// An empty rule removes the stored one.
		sentWithoutRule: '',
	},
	{
		title: 'the sale price window',
		kind: SALE_PRICE_WINDOW,
		field: SALE_PRICE_BODY_FIELD,
		newTicket: () => {
			const clientId = newClientId();
			setSalePriceChecked( clientId, true );

			return clientId;
		},
		fetched: ( rule ) => ( { id: 23, provider: 'tc', sale_price_data: { enabled: true, relative: rule } } ),
		stored: storedSalePrice,
		edited: { start: { mode: 'relative', value: 3, unit: UNIT_WEEKS }, end: { mode: 'specific' } },
		later: { start: { mode: 'relative', value: 4, unit: UNIT_DAYS }, end: { mode: 'specific' } },
		typed: {
			start: { mode: 'relative', value: '3', unit: String( UNIT_WEEKS ) },
			end: { mode: 'relative', value: '10', unit: String( UNIT_DAYS ) },
		},
		sentTyped: {
			start: { mode: 'relative', value: 3, unit: UNIT_WEEKS },
			end: { mode: 'relative', value: 10, unit: UNIT_DAYS },
		},
		notRelative: {
			start: { mode: 'now', value: 2, unit: UNIT_WEEKS },
			end: { mode: 'specific', value: 1, unit: UNIT_WEEKS },
		},
		// The server keeps the stored sale price rule, or the dates of a sale price saved without one.
		sentWithoutRule: null,
	},
];

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
 * Builds the legacy ticket state of a ticket block with a sale price and the sale price start its form holds; the form
 * sends the dates the editor always gives it, 2040-09-01 at 10:00 to the event start.
 *
 * @param {string}      clientId The client ID of the ticket block.
 * @param {boolean}     checked  Whether the sale price is checked.
 * @param {string|null} start    The specific sale price start date, `YYYY-MM-DD`, or `null`.
 *
 * @return {Object} The legacy state.
 */
function salePriceState( clientId, checked, start = null ) {
	const store = window.__tribe_common_store__;
	legacyTicketState( clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' );
	store.dispatch( legacyActions.setTempSalePriceChecked( clientId, checked ) );
	store.dispatch( legacyActions.setTicketTempSaleStartDate( clientId, start ?? '' ) );

	return store.getState();
}

/**
 * How each window is judged before a ticket block is saved, each with the event dates in the editor.
 *
 * @type {Object[]}
 */
const VALIDATION_CASES = [
	{
		title: 'the sales window',
		kind: SALES_WINDOW,
		field: BODY_FIELD,
		newTicket: newSalesWindowTicket,
		// The legacy state of a ticket block whose Create or Update judges this window.
		formState: ( clientId ) => legacyTicketState( clientId, '2040-09-01 10:00:00', '2040-10-20 19:00:00' ),
		stored: storedRule,
		valid: { start: relative( 2, UNIT_WEEKS ), end: relative( 1, UNIT_HOURS ) },
		endsBeforeStart: { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) },
		cleared: { start: relative( '', UNIT_WEEKS ), end: { mode: 'default' } },
		// The fields a request holding this window back still carries.
		stillSent: [],
	},
	{
		title: 'the sale price window',
		kind: SALE_PRICE_WINDOW,
		field: SALE_PRICE_BODY_FIELD,
		newTicket: newSalePriceTicket,
		formState: ( clientId ) => {
			dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );

			return salePriceState( clientId, true );
		},
		stored: storedSalePrice,
		valid: { start: salePriceBoundary( 2, UNIT_WEEKS ), end: salePriceBoundary( 1, UNIT_WEEKS ) },
		endsBeforeStart: { start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) },
		cleared: { start: salePriceBoundary( 2, UNIT_WEEKS ), end: salePriceBoundary( '', UNIT_WEEKS ) },
		stillSent: [ BODY_FIELD ],
	},
];

describe( 'the Relative Sale Dates block editor hooks', () => {
	// Each request belongs to a Tickets Commerce block unless a case says otherwise; the event dates are each case's own.
	beforeEach( () => {
		window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
		setEventState( DEFAULT_EVENT ).dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );
		delete window.tec.events;
	} );

	afterEach( () => {
		delete window.tribe;
		clearBlockEditorGlobals();
	} );

	describe.each( REQUEST_CASES )( 'on tec.tickets.blocks.fetchTicket, for $title', ( kindCase ) => {
		const { kind, field, fetched, stored } = kindCase;

		afterEach( () => {
			delete window.tribe;
			clearBlockEditorGlobals();
		} );

		it( 'should load the rule of the fetched ticket', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, fetched( stored ), {} );

			expect( getSavedRule( clientId, kind ) ).toStrictEqual( stored );
			expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toStrictEqual( stored );
		} );

		it( 'should load a fetched ticket without a rule as having none', () => {
			const clientId = newClientId();

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, fetched( null ), {} );

			expect( getSavedRule( clientId, kind ) ).toBeNull();
			expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toBeNull();
		} );

		it( 'should leave a ticket another provider sells out of the store and its requests', () => {
			const clientId = newClientId();
			setSalePriceChecked( clientId, true );

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, { ...fetched( stored ), provider: 'woo' }, {} );

			expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toBeUndefined();
			expect( buildBody( clientId ).has( field ) ).toBe( false );
		} );
	} );

	describe( 'on tec.tickets.blocks.fetchTicket', () => {
		it( 'should know no sale price rule for a fetched ticket without a sale price', () => {
			const clientId = newClientId();
			const ticket = { id: 23, provider: 'tc', sale_price_data: { enabled: false, relative: null } };

			doAction( 'tec.tickets.blocks.fetchTicket', clientId, ticket, {} );

			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toBeUndefined();
			expect( select( STORE_NAME ).getDraftRule( clientId, SALE_PRICE_WINDOW ) ).toBeUndefined();
		} );
	} );

	describe.each( REQUEST_CASES )( 'on tec.tickets.blocks.setBodyDetails, for $title', ( kindCase ) => {
		const { kind, field, newTicket } = kindCase;

		afterEach( () => {
			delete window.tribe;
			clearBlockEditorGlobals();
		} );

		it( 'should send the draft rule as JSON', () => {
			const clientId = newTicket();
			dispatch( STORE_NAME ).setRule( clientId, kindCase.stored, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.edited, kind );

			const body = buildBody( clientId );

			expect( JSON.parse( body.get( field ) ) ).toStrictEqual( kindCase.edited );
			expect( body.get( 'ticket[start_date]' ) ).toBe( '2026-10-01' );
		} );

		it( 'should send the value and unit of a relative boundary as integers', () => {
			const clientId = newTicket();
			dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.typed, kind );

			const sent = JSON.parse( buildBody( clientId ).get( field ) );

			expect( sent ).toStrictEqual( kindCase.sentTyped );
		} );

		it( 'should send only the mode of a boundary that is not relative', () => {
			const clientId = newTicket();
			const { start, end } = kindCase.notRelative;
			dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.notRelative, kind );

			const sent = JSON.parse( buildBody( clientId ).get( field ) );

			expect( sent ).toStrictEqual( { start: { mode: start.mode }, end: { mode: end.mode } } );
		} );

		it( 'should send what the window takes for no rule when the draft has none', () => {
			const clientId = newTicket();
			dispatch( STORE_NAME ).setRule( clientId, kindCase.stored, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, null, kind );

			expect( buildBody( clientId ).get( field ) ).toBe( kindCase.sentWithoutRule );
		} );

		it( 'should send no rule for a ticket the store knows nothing of', () => {
			const body = buildBody( newTicket() );

			expect( body.has( field ) ).toBe( false );
		} );

		it( 'should send no rule once the block of a new ticket moves off Tickets Commerce', () => {
			const clientId = newTicket();
			dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.edited, kind );
			window.__tribe_common_store__.dispatch( legacyActions.setTicketsProvider( WOO_PROVIDER ) );

			expect( buildBody( clientId ).has( field ) ).toBe( false );
		} );
	} );

	describe( 'on tec.tickets.blocks.setBodyDetails', () => {
		describe( 'with a sale price', () => {
			afterEach( () => {
				delete window.tribe;
				clearBlockEditorGlobals();
			} );

			it( 'should send the sales window and sale price rules side by side', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, true );
				dispatch( STORE_NAME ).setDraftRule( clientId, storedRule );
				dispatch( STORE_NAME ).setDraftRule( clientId, storedSalePrice, SALE_PRICE_WINDOW );

				const body = buildBody( clientId );

				expect( JSON.parse( body.get( BODY_FIELD ) ) ).toStrictEqual( storedRule );
				expect( JSON.parse( body.get( SALE_PRICE_BODY_FIELD ) ) ).toStrictEqual( storedSalePrice );
			} );

			it( 'should send no sale price rule while the sale price is unchecked', () => {
				const clientId = newClientId();
				setSalePriceChecked( clientId, false );
				dispatch( STORE_NAME ).setDraftRule( clientId, storedSalePrice, SALE_PRICE_WINDOW );

				expect( buildBody( clientId ).has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
			} );
		} );

		describe( 'with the event dates in the editor', () => {
			beforeEach( () => {
				setBlockEditorData();
				setEventState( DEFAULT_EVENT ).dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );
			} );

			it( 'should restore on Cancel the rule the server kept after a later request failed', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setRule( clientId, storedRule );
				dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
				buildBody( clientId );
				dispatch( STORE_NAME ).setDraftRule( clientId, {
					start: relative( 4, UNIT_DAYS ),
					end: { mode: 'default' },
				} );
				buildBody( clientId );
				doAction(
					'tec.tickets.blocks.ticketUpdated',
					clientId,
					23,
					{},
					{ id: 23, provider: 'tc', relative_sale_dates: editedRule }
				);

				doAction( 'tec.tickets.blocks.ticketCancelled', clientId );

				expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
			} );

			it( 'should keep both stored rules, by sending neither, while the sales window ends before it starts', () => {
				const clientId = newSalePriceTicket();
				dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );
				dispatch( STORE_NAME ).setDraftRule( clientId, storedSalePrice, SALE_PRICE_WINDOW );

				const body = buildBody( clientId );

				expect( body.has( BODY_FIELD ) ).toBe( false );
				expect( body.has( SALE_PRICE_BODY_FIELD ) ).toBe( false );
			} );
		} );

		describe.each( VALIDATION_CASES )( 'a draft of $title the server would reject', ( kindCase ) => {
			const { kind, field, newTicket } = kindCase;

			beforeEach( () => {
				setBlockEditorData();
				setEventState( DEFAULT_EVENT ).dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );
			} );

			it( 'should keep the stored rule, by sending none, while the draft ends before it starts', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, kindCase.stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.endsBeforeStart, kind );

				const body = buildBody( clientId );

				expect( body.has( field ) ).toBe( false );
				expect( body.get( 'ticket[start_date]' ) ).toBe( '2026-10-01' );
				kindCase.stillSent.forEach( ( sent ) => expect( body.has( sent ) ).toBe( true ) );
			} );

			// Saving the post updates every created ticket, whatever its Update button says.
			it( 'should go back to the stored rule on Cancel after a post save that held the draft back', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, kindCase.stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.endsBeforeStart, kind );
				buildBody( clientId );
				doAction( 'tec.tickets.blocks.ticketUpdated', clientId, 23, {} );

				doAction( 'tec.tickets.blocks.ticketCancelled', clientId );

				expect( getSavedRule( clientId, kind ) ).toStrictEqual( kindCase.stored );
				expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toStrictEqual( kindCase.stored );
			} );

			// The server rejects such a rule whatever the event dates, so it is held back even without them.
			it( 'should keep the stored rule, by sending none, while a number is cleared and the event dates cannot be read', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, kindCase.stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.cleared, kind );
				delete window.tec.events;

				expect( buildBody( clientId ).has( field ) ).toBe( false );
			} );

			it( 'should send a valid rule while the event dates cannot be read', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.valid, kind );
				delete window.tec.events;

				expect( JSON.parse( buildBody( clientId ).get( field ) ) ).toStrictEqual( kindCase.valid );
			} );

			it( 'should send a draft that starts before it ends', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setDraftRule( clientId, kindCase.valid, kind );

				expect( JSON.parse( buildBody( clientId ).get( field ) ) ).toStrictEqual( kindCase.valid );
			} );
		} );
	} );

	describe.each( REQUEST_CASES )( 'on tec.tickets.blocks.ticketCancelled, for $title', ( kindCase ) => {
		const { kind, newTicket, stored, edited } = kindCase;

		afterEach( () => {
			delete window.tribe;
			clearBlockEditorGlobals();
		} );

		it( 'should restore the saved rule into the draft', () => {
			const clientId = newTicket();
			dispatch( STORE_NAME ).setRule( clientId, stored, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			doAction( 'tec.tickets.blocks.ticketCancelled', clientId );

			expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toStrictEqual( stored );
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

	describe( 'on tec.tickets.blocks.Ticket.SalePrice.renderPickers', () => {
		const Row = () => null;
		const StartPicker = () => null;
		const EndPicker = () => null;

		/**
		 * Filters the sale price dates row of a ticket block for the given providers.
		 *
		 * @param {string} provider       The ticket provider the tickets block uses.
		 * @param {string} clientId       The client ID of the ticket block.
		 * @param {string} ticketProvider The provider slug the ticket keeps once fetched, or an empty string for a new one.
		 *
		 * @return {Object} What the sale price section renders in place of its dates row.
		 */
		function filterSalePrice( provider, clientId, ticketProvider = '' ) {
			const tickets = { allClientIds: [ clientId ], byClientId: { [ clientId ]: { provider: ticketProvider } } };
			window.tribe = { tickets: { data: { blocks: { selectors: legacySelectors } } } };
			window.__tribe_common_store__ = {
				getState: () => ( { tickets: { blocks: { ticket: { provider, tickets } } } } ),
			};

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

		it( 'should leave the dates row alone for an older ticket another provider sells under a Tickets Commerce block', () => {
			const rendered = filterSalePrice( TICKETS_COMMERCE, newClientId(), 'woo' );

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
			setEventState( DEFAULT_EVENT ).dispatch( legacyActions.setTicketsProvider( TICKETS_COMMERCE ) );
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

		it( 'should leave the dates alone once the block of a new ticket moves off Tickets Commerce', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, storedRule );
			window.__tribe_common_store__.dispatch( legacyActions.setTicketsProvider( WOO_PROVIDER ) );

			expect( filterDates( clientId ) ).toStrictEqual( ticketDates );
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

		it( 'should leave the button alone for an older ticket another provider sells under a Tickets Commerce block', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, { start: relative( 1, UNIT_HOURS ), end: relative( 2, UNIT_HOURS ) } );
			window.__tribe_common_store__.dispatch( legacyActions.setTicketProvider( clientId, 'woo' ) );

			expect( isConfirmDisabled( clientId, false ) ).toBe( false );
		} );

		describe.each( VALIDATION_CASES )( 'for $title', ( kindCase ) => {
			const { kind } = kindCase;

			/**
			 * Asks whether the Create or Update button of a new ticket block with a draft rule of the window is disabled.
			 *
			 * @param {Object|null|undefined} rule       The draft rule.
			 * @param {boolean}               isDisabled Whether the legacy checks disable it.
			 *
			 * @return {boolean} Whether the button is disabled.
			 */
			function isDisabledWith( rule, isDisabled = false ) {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, rule, kind );

				return isConfirmDisabled( clientId, isDisabled, kindCase.formState( clientId ) );
			}

			it( 'should disable Create and Update while the window ends before it starts', () => {
				expect( isDisabledWith( kindCase.endsBeforeStart ) ).toBe( true );
			} );

			it( 'should leave the button alone for a window that starts before it ends', () => {
				expect( isDisabledWith( kindCase.valid ) ).toBe( false );
				expect( isDisabledWith( kindCase.valid, true ) ).toBe( true );
			} );

			it( 'should disable Create and Update while a relative start or end has no number', () => {
				expect( isDisabledWith( kindCase.cleared ) ).toBe( true );
			} );

			it( 'should leave the button alone for a ticket another provider sells', () => {
				window.__tribe_common_store__.dispatch( legacyActions.setTicketsProvider( WOO_PROVIDER ) );

				expect( isDisabledWith( kindCase.endsBeforeStart ) ).toBe( false );
			} );

			it( 'should leave the button alone for a window saved without a rule, or not yet given one', () => {
				expect( isDisabledWith( null ) ).toBe( false );
				expect( isDisabledWith( undefined ) ).toBe( false );
			} );

			it( 'should leave the button alone while the event dates cannot be read', () => {
				delete window.tec.events;

				expect( isDisabledWith( kindCase.endsBeforeStart ) ).toBe( false );
			} );

			// The server rejects a relative boundary without a whole number before it reads any date.
			it( 'should disable Create and Update while a relative number is cleared and the event dates cannot be read', () => {
				delete window.tec.events;

				expect( isDisabledWith( kindCase.cleared ) ).toBe( true );
				expect( isDisabledWith( kindCase.valid ) ).toBe( false );
			} );
		} );

		describe( 'with a sale price', () => {
			/**
			 * Asks whether the Create or Update button of a ticket block with a sale price rule is disabled.
			 *
			 * @param {Object}      salePriceRule      The sale price draft rule.
			 * @param {Object}      options            The sale price state.
			 * @param {boolean}     options.checked    Whether the sale price is checked.
			 * @param {string|null} options.start      The specific sale price start date the form holds.
			 * @param {Object|null} options.windowRule The sales window draft rule.
			 *
			 * @return {boolean} Whether the button is disabled.
			 */
			function isDisabledWith(
				salePriceRule,
				{ checked = true, start = null, windowRule = salesWindowRule } = {}
			) {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, windowRule );
				dispatch( STORE_NAME ).setDraftRule( clientId, salePriceRule, SALE_PRICE_WINDOW );

				return isConfirmDisabled( clientId, false, salePriceState( clientId, checked, start ) );
			}

			it( 'should disable Create and Update while a relative sale price start falls before the sales window', () => {
				const rule = { start: salePriceBoundary( 5, UNIT_WEEKS ), end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule ) ).toBe( true );
			} );

			it( 'should disable Create and Update while a specific sale price start falls before the sales window', () => {
				const rule = { start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { start: '2040-09-01' } ) ).toBe( true );
				expect( isDisabledWith( rule, { start: '2040-09-29' } ) ).toBe( false );
			} );

			// The legacy store keeps an empty sale price date it loads as `Invalid date`: a start without a date.
			it( 'should judge a specific sale price start the legacy store holds as Invalid date as one without a date', () => {
				const rule = { start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { start: 'Invalid date' } ) ).toBe( false );
			} );

			it( 'should judge a Now start by the day sales open, never as outside the sales window', () => {
				const endingIn = ( weeks ) => ( {
					start: { mode: 'now' },
					end: salePriceBoundary( weeks, UNIT_WEEKS ),
				} );

				expect( isDisabledWith( endingIn( 1 ) ) ).toBe( false );
				expect( isDisabledWith( endingIn( 5 ) ) ).toBe( true );
			} );

			it( 'should leave the button alone for a sale price the save drops for not being lower than the price', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );
				dispatch( STORE_NAME ).setDraftRule(
					clientId,
					{ start: salePriceBoundary( 1, UNIT_WEEKS ), end: salePriceBoundary( 2, UNIT_WEEKS ) },
					SALE_PRICE_WINDOW
				);
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

			// Without a sales window rule, the ticket's own dates hold: 2040-09-01 at 10:00 to the event start.
			it( "should judge the sale price against the ticket's own dates without a sales window rule", () => {
				const rule = { start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) };

				expect( isDisabledWith( rule, { windowRule: null, start: '2040-08-31' } ) ).toBe( true );
				expect( isDisabledWith( rule, { windowRule: null, start: '2040-09-02' } ) ).toBe( false );
			} );

			it( 'should judge the sale price dates of the state the dashboard passes', () => {
				const clientId = newClientId();
				dispatch( STORE_NAME ).setDraftRule( clientId, salesWindowRule );
				dispatch( STORE_NAME ).setDraftRule(
					clientId,
					{ start: { mode: 'specific' }, end: salePriceBoundary( 1, UNIT_WEEKS ) },
					SALE_PRICE_WINDOW
				);
				const state = salePriceState( clientId, true, '2040-09-29' );

				window.__tribe_common_store__.dispatch(
					legacyActions.setTicketTempSaleStartDate( clientId, '2040-09-01' )
				);

				expect( isConfirmDisabled( clientId, false, state ) ).toBe( false );
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
		afterEach( () => {
			delete window.tribe;
			clearBlockEditorGlobals();
		} );

		describe.each( REQUEST_CASES )( 'for $title', ( kindCase ) => {
			const { kind, newTicket, stored, edited, later } = kindCase;

			it( 'should keep the rule the request carried as the saved one', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );
				buildBody( clientId );

				doAction( hook, clientId, 23, {} );

				expect( getSavedRule( clientId, kind ) ).toStrictEqual( edited );
			} );

			it( 'should keep as saved the rule the server answered with, not the one a later request carried', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );
				buildBody( clientId );
				dispatch( STORE_NAME ).setDraftRule( clientId, later, kind );
				buildBody( clientId );

				doAction( hook, clientId, 23, {}, kindCase.fetched( edited ) );

				expect( getSavedRule( clientId, kind ) ).toStrictEqual( edited );
				expect( select( STORE_NAME ).getDraftRule( clientId, kind ) ).toStrictEqual( later );
			} );

			it( 'should keep the sent rule as saved for an answer without the rule', () => {
				const clientId = newTicket();
				dispatch( STORE_NAME ).setRule( clientId, stored, kind );
				dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );
				buildBody( clientId );

				doAction( hook, clientId, 23, {}, { id: 23, provider: 'tc' } );

				expect( getSavedRule( clientId, kind ) ).toStrictEqual( edited );
			} );
		} );

		// As a reload does: the server drops the rule along with an unchecked sale price.
		it( 'should forget the sale price rule of a ticket the server answered without a sale price', () => {
			const clientId = newClientId();
			setSalePriceChecked( clientId, false );
			dispatch( STORE_NAME ).setRule( clientId, storedSalePrice, SALE_PRICE_WINDOW );
			buildBody( clientId );

			const answer = { id: 23, provider: 'tc', sale_price_data: { enabled: false, relative: null } };

			doAction( hook, clientId, 23, {}, answer );

			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toBeUndefined();
		} );
	} );
} );
