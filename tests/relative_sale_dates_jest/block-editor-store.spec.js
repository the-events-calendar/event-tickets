import { dispatch, getStoreState, select } from '@wordpress/data';
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


/**
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {Object|null|undefined} The rule the store keeps as the ticket's saved one, which no selector exposes.
 */
function getSavedRule( clientId ) {
	return getStoreState( STORE_NAME )[ clientId ]?.saved;
}

describe( 'the Relative Sale Dates block editor store', () => {
	it( 'should know nothing of a ticket it was never given a rule for', () => {
		const clientId = newClientId();

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeUndefined();
		expect( getSavedRule( clientId ) ).toBeUndefined();
	} );

	it( 'should hold the rule a ticket was loaded with as both saved and draft', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setRule( clientId, savedRule );

		expect( getSavedRule( clientId ) ).toStrictEqual( savedRule );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
	} );

	it( 'should hold a ticket loaded without a rule as null, not as unknown', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setRule( clientId, null );

		expect( getSavedRule( clientId ) ).toBeNull();
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toBeNull();
	} );

	it( 'should change the draft and leave the saved rule alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );

		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
		expect( getSavedRule( clientId ) ).toStrictEqual( savedRule );
	} );

	it( 'should hold the draft of a new ticket that has no saved rule yet', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
		expect( getSavedRule( clientId ) ).toBeUndefined();
	} );

	it( 'should save the rule the request carried as the saved rule', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
		dispatch( STORE_NAME ).setSentRule( clientId, editedRule );

		dispatch( STORE_NAME ).saveSentRule( clientId );

		expect( getSavedRule( clientId ) ).toStrictEqual( editedRule );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
	} );

	it( 'should save an empty rule the request carried, which removes the rule', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, null );
		dispatch( STORE_NAME ).setSentRule( clientId, null );

		dispatch( STORE_NAME ).saveSentRule( clientId );

		expect( getSavedRule( clientId ) ).toBeNull();
	} );

	it( 'should keep the saved rule when the request carried none', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );
		dispatch( STORE_NAME ).setSentRule( clientId, undefined );

		dispatch( STORE_NAME ).saveSentRule( clientId );

		expect( getSavedRule( clientId ) ).toStrictEqual( savedRule );
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
	} );

	it( 'should keep a confirmed rule as saved and leave the draft alone', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		dispatch( STORE_NAME ).saveConfirmedRule( clientId, null );

		expect( getSavedRule( clientId ) ).toBeNull();
		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( editedRule );
	} );

	it( 'should leave a ticket it knows nothing of out of the store when its rule is confirmed', () => {
		const clientId = newClientId();

		dispatch( STORE_NAME ).saveConfirmedRule( clientId, savedRule );

		expect( getStoreState( STORE_NAME )[ clientId ] ).toBeUndefined();
	} );

	it( 'should restore the saved rule into the draft on cancel', () => {
		const clientId = newClientId();
		dispatch( STORE_NAME ).setRule( clientId, savedRule );
		dispatch( STORE_NAME ).setDraftRule( clientId, editedRule );

		dispatch( STORE_NAME ).resetDraftRule( clientId );

		expect( select( STORE_NAME ).getDraftRule( clientId ) ).toStrictEqual( savedRule );
		expect( getSavedRule( clientId ) ).toStrictEqual( savedRule );
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
} );
