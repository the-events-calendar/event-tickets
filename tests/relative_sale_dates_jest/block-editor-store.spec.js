import { dispatch, getStoreState, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';
import '@tec/tickets/relative-sale-dates/block-editor/store';

jest.mock( '@wordpress/data', () => require( './wordpress-data-registry' ) );

const UNIT_HOURS = 3600;
const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

const savedRule = {
	start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
	end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' },
};

const editedRule = {
	start: { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'start' },
	end: { mode: 'default' },
};

const savedSalePrice = {
	start: { mode: 'now' },
	end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
};

const editedSalePrice = {
	start: { mode: 'relative', value: 3, unit: UNIT_WEEKS },
	end: { mode: 'specific' },
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
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} kind     The window kind.
 *
 * @return {Object|null|undefined} The rule the store keeps as the ticket's saved one, which no selector exposes.
 */
function getSavedRule( clientId, kind = SALES_WINDOW ) {
	return getStoreState( STORE_NAME )[ clientId ]?.[ kind.id ]?.saved;
}

/**
 * @param {string} clientId The client ID of the ticket block.
 * @param {Object} kind     The window kind.
 *
 * @return {Object|null|undefined} The rule the ticket block is being edited to.
 */
function getDraftRule( clientId, kind = SALES_WINDOW ) {
	return select( STORE_NAME ).getDraftRule( clientId, kind );
}

describe( 'the Relative Sale Dates block editor store', () => {
	describe.each( [
		{
			title: 'the sales window',
			kind: SALES_WINDOW,
			other: SALE_PRICE_WINDOW,
			otherSaved: savedSalePrice,
			saved: savedRule,
			edited: editedRule,
		},
		{
			title: 'the sale price window',
			kind: SALE_PRICE_WINDOW,
			other: SALES_WINDOW,
			otherSaved: savedRule,
			saved: savedSalePrice,
			edited: editedSalePrice,
		},
	] )( 'with the rule of $title', ( { kind, other, otherSaved, saved, edited } ) => {
		it( 'should know nothing of a ticket it was never given a rule for', () => {
			const clientId = newClientId();

			expect( getDraftRule( clientId, kind ) ).toBeUndefined();
			expect( getSavedRule( clientId, kind ) ).toBeUndefined();
		} );

		it( 'should hold the rule a ticket was loaded with as both saved and draft', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setRule( clientId, saved, kind );

			expect( getSavedRule( clientId, kind ) ).toStrictEqual( saved );
			expect( getDraftRule( clientId, kind ) ).toStrictEqual( saved );
		} );

		it( 'should hold a ticket loaded without a rule as null, not as unknown', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setRule( clientId, null, kind );

			expect( getSavedRule( clientId, kind ) ).toBeNull();
			expect( getDraftRule( clientId, kind ) ).toBeNull();
		} );

		it( 'should change the draft and leave the saved rule and the other window alone', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setRule( clientId, null, other );

			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
			expect( getSavedRule( clientId, kind ) ).toStrictEqual( saved );
			expect( getDraftRule( clientId, other ) ).toBeNull();
		} );

		it( 'should hold the draft of a new ticket that has no saved rule yet', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
			expect( getSavedRule( clientId, kind ) ).toBeUndefined();
		} );

		it( 'should save the rule the request carried as the saved rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );
			dispatch( STORE_NAME ).setSentRule( clientId, edited, kind );

			dispatch( STORE_NAME ).saveSentRule( clientId );

			expect( getSavedRule( clientId, kind ) ).toStrictEqual( edited );
			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
		} );

		it( 'should keep the saved rule when the request carried none', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );
			dispatch( STORE_NAME ).setSentRule( clientId, undefined, kind );

			dispatch( STORE_NAME ).saveSentRule( clientId );

			expect( getSavedRule( clientId, kind ) ).toStrictEqual( saved );
			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
		} );

		it( 'should keep a confirmed rule as saved and leave the draft alone', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			dispatch( STORE_NAME ).saveConfirmedRule( clientId, null, kind );

			expect( getSavedRule( clientId, kind ) ).toBeNull();
			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
		} );

		// As a reload does: a sale price saved unchecked has no rule to load.
		it( 'should forget the saved rule the server answered without, and leave the draft alone', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			dispatch( STORE_NAME ).saveConfirmedRule( clientId, undefined, kind );

			expect( getSavedRule( clientId, kind ) ).toBeUndefined();
			expect( getDraftRule( clientId, kind ) ).toStrictEqual( edited );
		} );

		it( 'should leave a ticket it knows nothing of out of the store when its rule is confirmed', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).saveConfirmedRule( clientId, saved, kind );

			expect( getStoreState( STORE_NAME )[ clientId ] ).toBeUndefined();
		} );

		// A Tickets Commerce answer carries the sale price data even for a ticket whose sale price was never shown.
		it( 'should leave the window of a known ticket that has none out of the store when its rule is confirmed', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, otherSaved, other );

			dispatch( STORE_NAME ).saveConfirmedRule( clientId, saved, kind );

			expect( getStoreState( STORE_NAME )[ clientId ] ).not.toHaveProperty( kind.id );
			expect( getSavedRule( clientId, other ) ).toStrictEqual( otherSaved );
		} );

		it( 'should restore the saved rule into the draft on cancel', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, saved, kind );
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			dispatch( STORE_NAME ).resetDraftRule( clientId );

			expect( getDraftRule( clientId, kind ) ).toStrictEqual( saved );
			expect( getSavedRule( clientId, kind ) ).toStrictEqual( saved );
		} );

		it( 'should discard on cancel the draft of a ticket never saved with a rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, edited, kind );

			dispatch( STORE_NAME ).resetDraftRule( clientId );

			expect( getDraftRule( clientId, kind ) ).toBeUndefined();
		} );
	} );

	it( 'should save an empty rule the request carried, which removes the rule', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, null );
		dispatch( STORE_NAME ).setSentRule( clientId, null );

		dispatch( STORE_NAME ).saveSentRule( clientId );

		expect( getSavedRule( clientId ) ).toBeNull();
	} );

	it( 'should keep the rules of each ticket apart', () => {
		const firstClientId = newClientId();
		const secondClientId = newClientId();
		dispatch( STORE_NAME ).setRule( firstClientId, savedRule );
		dispatch( STORE_NAME ).setRule( secondClientId, savedRule );

		dispatch( STORE_NAME ).setDraftRule( firstClientId, editedRule );
		dispatch( STORE_NAME ).saveSentRule( secondClientId );
		dispatch( STORE_NAME ).resetDraftRule( secondClientId );

		expect( select( STORE_NAME ).getDraftRule( firstClientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getDraftRule( secondClientId ) ).toStrictEqual( savedRule );
		expect( getSavedRule( secondClientId ) ).toStrictEqual( savedRule );
	} );

	describe( 'with both rules', () => {
		it( 'should know nothing of the sale price of a ticket it was never given a sale price rule for', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );

			expect( getDraftRule( clientId, SALE_PRICE_WINDOW ) ).toBeUndefined();
			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toBeUndefined();
		} );

		it( 'should keep the sales window rule when the sale price rule is loaded', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setRule( clientId, savedSalePrice, SALE_PRICE_WINDOW );

			expect( getDraftRule( clientId ) ).toStrictEqual( savedRule );
			expect( getSavedRule( clientId ) ).toStrictEqual( savedRule );
			expect( getDraftRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( savedSalePrice );
		} );

		it( 'should keep the sale price rule when the sales window rule is loaded', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setRule( clientId, savedSalePrice, SALE_PRICE_WINDOW );
			dispatch( STORE_NAME ).setRule( clientId, savedRule );

			expect( getDraftRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( savedSalePrice );
			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( savedSalePrice );
			expect( getDraftRule( clientId ) ).toStrictEqual( savedRule );
		} );

		it( 'should save both rules a request carried together', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setRule( clientId, savedSalePrice, SALE_PRICE_WINDOW );
			dispatch( STORE_NAME ).setSentRule( clientId, editedRule );
			dispatch( STORE_NAME ).setSentRule( clientId, editedSalePrice, SALE_PRICE_WINDOW );

			dispatch( STORE_NAME ).saveSentRule( clientId );

			expect( getSavedRule( clientId ) ).toStrictEqual( editedRule );
			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( editedSalePrice );
		} );

		it( 'should save the sale price rule a request carried for a new ticket that has no sales window rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftRule( clientId, editedSalePrice, SALE_PRICE_WINDOW );
			dispatch( STORE_NAME ).setSentRule( clientId, editedSalePrice, SALE_PRICE_WINDOW );

			dispatch( STORE_NAME ).saveSentRule( clientId );

			expect( getSavedRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( editedSalePrice );
		} );

		it( 'should restore both saved rules into the drafts on cancel', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setRule( clientId, savedSalePrice, SALE_PRICE_WINDOW );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedSalePrice, SALE_PRICE_WINDOW );

			dispatch( STORE_NAME ).resetDraftRule( clientId );

			expect( getDraftRule( clientId ) ).toStrictEqual( savedRule );
			expect( getDraftRule( clientId, SALE_PRICE_WINDOW ) ).toStrictEqual( savedSalePrice );
		} );
	} );
} );
