import * as legacyActions from '@moderntribe/tickets/data/blocks/ticket/actions';
import * as legacySelectors from '@moderntribe/tickets/data/blocks/ticket/selectors';
import { readTicketFormWindowDates } from '@tec/tickets/relative-sale-dates/block-editor/common-store-bridge';
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

	it( 'should read no sale price dates for a ticket the legacy store does not hold', () => {
		expect( readTicketFormWindowDates( CLIENT_ID, BLOCK_SALE_PRICE_WINDOW ) ).toStrictEqual( {
			start: null,
			end: null,
		} );
	} );
} );
