import { dispatch, select } from '@wordpress/data';
import { STORE_NAME } from '@tec/tickets/relative-sale-dates/block-editor/store/constants';
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

describe( 'the Relative Sale Dates block editor store', () => {
	it( 'should know nothing of a ticket it was never given a rule for', () => {
		const clientId = newClientId();

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeUndefined();
		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toBeUndefined();
	} );

	it( 'should hold the rule a ticket was loaded with as both saved and draft', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setRule( clientId, savedRule );

		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( savedRule );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
	} );

	it( 'should hold a ticket loaded without a rule as null, not as unknown', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setRule( clientId, null );

		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toBeNull();
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeNull();
	} );

	it( 'should change the draft and leave the saved rule alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );

		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( savedRule );
	} );

	it( 'should hold the draft of a new ticket that has no saved rule yet', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toBeUndefined();
	} );

	it( 'should save the draft as the saved rule', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		dispatch( STORE_NAME ).saveDraftRule( clientId );

		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
	} );

	it( 'should save a draft that removes the rule', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, null );

		dispatch( STORE_NAME ).saveDraftRule( clientId );

		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toBeNull();
	} );

	it( 'should restore the saved rule into the draft on cancel', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		dispatch( STORE_NAME ).resetDraftRule( clientId );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
		expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( savedRule );
	} );

	it( 'should keep the rules of each ticket apart', () => {
		const firstClientId = newClientId();
		const secondClientId = newClientId();
		dispatch( STORE_NAME ).setRule( firstClientId, savedRule );
		dispatch( STORE_NAME ).setRule( secondClientId, savedRule );

		dispatch( STORE_NAME ).setDraftRule( firstClientId, editedRule );
		dispatch( STORE_NAME ).saveDraftRule( secondClientId );
		dispatch( STORE_NAME ).resetDraftRule( secondClientId );

		expect( select( STORE_NAME ).getDraftRule( firstClientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getDraftRule( secondClientId ) ).toStrictEqual( savedRule );
		expect( select( STORE_NAME ).getSavedRule( secondClientId ) ).toStrictEqual( savedRule );
	} );

	describe( 'with the sale price rule', () => {
		const savedSalePriceRule = {
			start: { mode: 'now' },
			end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
		};

		const editedSalePriceRule = {
			start: { mode: 'relative', value: 3, unit: UNIT_WEEKS },
			end: { mode: 'specific' },
		};

		it( 'should know nothing of the sale price of a ticket it was never given a sale price rule for', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );

			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toBeUndefined();
			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toBeUndefined();
		} );

		it( 'should hold the sale price rule a ticket was loaded with as both saved and draft', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );

			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
		} );

		it( 'should keep the sales window rule when the sale price rule is loaded', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );

			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
			expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( savedRule );
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
		} );

		it( 'should keep the sale price rule when the sales window rule is loaded', () => {
			const clientId = newClientId();

			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );
			dispatch( STORE_NAME ).setRule( clientId, savedRule );

			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
		} );

		it( 'should change the sale price draft and leave its saved rule and the sales window alone', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );

			dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, editedSalePriceRule );

			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( editedSalePriceRule );
			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
		} );

		it( 'should save both drafts of a ticket together', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
			dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, editedSalePriceRule );

			dispatch( STORE_NAME ).saveDraftRule( clientId );

			expect( select( STORE_NAME ).getSavedRule( clientId ) ).toStrictEqual( editedRule );
			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( editedSalePriceRule );
		} );

		it( 'should save the sale price draft of a new ticket that has no sales window rule', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, editedSalePriceRule );

			dispatch( STORE_NAME ).saveDraftRule( clientId );

			expect( select( STORE_NAME ).getSavedSalePriceRule( clientId ) ).toStrictEqual( editedSalePriceRule );
		} );

		it( 'should restore both saved rules into the drafts on cancel', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setRule( clientId, savedRule );
			dispatch( STORE_NAME ).setSalePriceRule( clientId, savedSalePriceRule );
			dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
			dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, editedSalePriceRule );

			dispatch( STORE_NAME ).resetDraftRule( clientId );

			expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toStrictEqual( savedSalePriceRule );
		} );

		it( 'should discard the sale price draft of a ticket never saved with one on cancel', () => {
			const clientId = newClientId();
			dispatch( STORE_NAME ).setDraftSalePriceRule( clientId, editedSalePriceRule );

			dispatch( STORE_NAME ).resetDraftRule( clientId );

			expect( select( STORE_NAME ).getDraftSalePriceRule( clientId ) ).toBeUndefined();
		} );
	} );
} );
