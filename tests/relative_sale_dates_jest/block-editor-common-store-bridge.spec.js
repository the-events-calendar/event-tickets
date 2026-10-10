import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import {
	isSalePriceKept,
	readTicketFormWindowDates,
} from '@tec/tickets/relative-sale-dates/block-editor/common-store-bridge';
import {
	BLOCK_SALES_WINDOW,
	BLOCK_SALE_PRICE_WINDOW,
} from '@tec/tickets/relative-sale-dates/block-editor/window-kinds';
import { DEFAULT_EVENT, clearBlockEditorGlobals, setEventState, setTicketFormDates } from './block-editor-event-state';

// `@wordpress/element` is not installed: the block editor provides it at runtime, as a re-export of React.
jest.mock( '@wordpress/element', () => require( 'react' ) );

const CLIENT_ID = 'ticket-block-1';

let store;

/**
 * Registers the ticket block with the sale price dates its form holds, as the legacy store keeps them.
 *
 * @param {string} start The sale price start date the legacy store holds.
 * @param {string} end   The sale price end date the legacy store holds.
 *
 * @return {void}
 */
function setSalePriceDates( start, end ) {
	store.dispatch( legacyActions.registerTicketBlock( CLIENT_ID ) );
	store.dispatch( legacyActions.setTicketTempSaleStartDate( CLIENT_ID, start ) );
	store.dispatch( legacyActions.setTicketTempSaleEndDate( CLIENT_ID, end ) );
}

beforeEach( () => {
	window.tribe = { tickets: { data: { blocks: { actions: legacyActions, selectors: legacySelectors } } } };
	store = setEventState( DEFAULT_EVENT );
} );

afterEach( () => {
	delete window.tribe;
	clearBlockEditorGlobals();
} );

describe( 'readTicketFormWindowDates', () => {
	it( 'should read the sale start and end the form sends for the sales window', () => {
		setTicketFormDates( store, CLIENT_ID, '2040-10-01 10:00:00', '2040-10-13 18:30:00' );

		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALES_WINDOW ) ).toStrictEqual( {
			start: '2040-10-01 10:00:00',
			end: '2040-10-13 18:30:00',
		} );
	} );

	it( 'should read the sale price dates the form sends as the start of their days', () => {
		setSalePriceDates( '2040-10-06', '2040-10-13' );

		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALE_PRICE_WINDOW ) ).toStrictEqual( {
			start: '2040-10-06 00:00:00',
			end: '2040-10-13 00:00:00',
		} );
	} );

	// The legacy code keeps an empty sale price date it loads or saves as the string `Invalid date`.
	it.each( [
		[ 'kept as `Invalid date`', 'Invalid date' ],
		[ 'left empty', '' ],
	] )( 'should read a sale price date %s as no date', ( label, date ) => {
		setSalePriceDates( date, '2040-10-13' );

		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALE_PRICE_WINDOW ) ).toStrictEqual( {
			start: null,
			end: '2040-10-13 00:00:00',
		} );
	} );

	it( 'should read the dates from the legacy state it is given rather than the store', () => {
		setTicketFormDates( store, CLIENT_ID, '2040-10-01 10:00:00', '2040-10-13 18:30:00' );
		const state = store.getState();
		setTicketFormDates( store, CLIENT_ID, '2040-10-02 10:00:00', '2040-10-14 18:30:00' );

		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALES_WINDOW, state ) ).toStrictEqual( {
			start: '2040-10-01 10:00:00',
			end: '2040-10-13 18:30:00',
		} );
	} );

	it( 'should read no sale price dates for a ticket the legacy store does not hold', () => {
		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALE_PRICE_WINDOW ) ).toStrictEqual( {
			start: null,
			end: null,
		} );
	} );
} );

describe( 'isSalePriceKept', () => {
	/**
	 * Reads whether a save keeps the checked sale price of the ticket block with the given prices.
	 *
	 * @param {string} price     The price the form holds.
	 * @param {string} salePrice The sale price the form holds.
	 *
	 * @return {boolean} Whether the save keeps the sale price.
	 */
	function isKeptWith( price, salePrice ) {
		store.dispatch( legacyActions.registerTicketBlock( CLIENT_ID ) );
		store.dispatch( legacyActions.setTempSalePriceChecked( CLIENT_ID, true ) );
		store.dispatch( legacyActions.setTicketTempPrice( CLIENT_ID, price ) );
		store.dispatch( legacyActions.setTempSalePrice( CLIENT_ID, salePrice ) );

		return isSalePriceKept( store.getState(), CLIENT_ID );
	}

	it.each( [
		[ '20.00', '15', true ],
		[ '20', '20.00', false ],
		[ '20', '25', false ],
		// The server compares a number with an empty price as strings, and drops the sale price.
		[ '', '10', false ],
		[ '', '', false ],
	] )( 'should judge a price of "%s" with a sale price of "%s" as the server does', ( price, salePrice, kept ) => {
		expect( isKeptWith( price, salePrice ) ).toBe( kept );
	} );

	it( 'should not keep an unchecked sale price', () => {
		isKeptWith( '20', '15' );
		store.dispatch( legacyActions.setTempSalePriceChecked( CLIENT_ID, false ) );

		expect( isSalePriceKept( store.getState(), CLIENT_ID ) ).toBe( false );
	} );
} );
