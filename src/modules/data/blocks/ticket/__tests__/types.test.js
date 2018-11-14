/**
 * Internal dependencies
 */
import { PREFIX_TICKETS_STORE } from '@moderntribe/tickets/data/utils';
import { types } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Tickets block types', () => {
	describe( 'Global tickets types', () => {
		test( 'Use the prefix on the types', () => {
			expect( types.SET_TICKET_TOTAL_SHARED_CAPACITY )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_TOTAL_SHARED_CAPACITY` );
			expect( types.SET_TICKET_HEADER )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_HEADER` );
			expect( types.SET_TICKET_SETTINGS_OPEN )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_SETTINGS_OPEN` );
			expect( types.SET_TICKET_TMP_TICKET_SHARED_CAPACITY )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_TMP_TICKET_SHARED_CAPACITY` );
			expect( types.SET_CHILD_BLOCK_SELECTED )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_CHILD_BLOCK_SELECTED` );
			expect( types.SET_PARENT_BLOCK_SELECTED )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_PARENT_BLOCK_SELECTED` );
			expect( types.SET_ACTIVE_CHILD_BLOCK_ID )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_ACTIVE_CHILD_BLOCK_ID` );
			expect( types.SET_UPDATE_TICKET ).toBe( `${ PREFIX_TICKETS_STORE }/SET_UPDATE_TICKET` );
			expect( types.REMOVE_TICKET_BLOCK ).toBe( `${ PREFIX_TICKETS_STORE }/REMOVE_TICKET_BLOCK` );
			expect( types.SET_INITIAL_STATE ).toBe( `${ PREFIX_TICKETS_STORE }/SET_INITIAL_STATE` );
			expect( types.SET_PARENT_BLOCK_LOADING )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_PARENT_BLOCK_LOADING` );
			expect( types.SET_PROVIDER )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_PROVIDER` );
		} );
	} );

	describe( 'Single ticket types', () => {
		test( 'ticket store prefix', () => {
			expect( types.SET_TICKET_ID ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_ID` );
			expect( types.SET_TICKET_CAPACITY_TYPE )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_CAPACITY_TYPE` );
			expect( types.SET_TICKET_START_TIME )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_START_TIME` );
			expect( types.SET_TICKET_END_TIME )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_END_TIME` );
			expect( types.SET_TICKET_START_DATE )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_START_DATE` );
			expect( types.SET_TICKET_END_DATE )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_END_DATE` );
			expect( types.SET_TICKET_DATE_PRISTINE )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_DATE_PRISTINE` );
			expect( types.SET_TICKET_TITLE ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_TITLE` );
			expect( types.SET_TICKET_DESCRIPTION )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_DESCRIPTION` );
			expect( types.SET_TICKET_PRICE ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_PRICE` );
			expect( types.SET_TICKET_SKU ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_SKU` );
			expect( types.SET_TICKET_BLOCK_ID ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_BLOCK_ID` );
			expect( types.SET_TICKET_CAPACITY ).toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_CAPACITY` );
			expect( types.SET_CREATE_NEW_TICKET )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_CREATE_NEW_TICKET` );
			expect( types.SET_TICKET_IS_EDITING )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_IS_EDITING` );
			expect( types.SET_TICKET_START_DATE_MOMENT )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_START_DATE_MOMENT` );
			expect( types.SET_TICKET_END_DATE_MOMENT )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_END_DATE_MOMENT` );
			expect( types.SET_TICKET_IS_LOADING )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_IS_LOADING` );
			expect( types.SET_TICKET_HAS_BEEN_CREATED )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_HAS_BEEN_CREATED` );
			expect( types.REQUEST_REMOVAL_OF_TICKET_BLOCK )
				.toBe( `${ PREFIX_TICKETS_STORE }/REQUEST_REMOVAL_OF_TICKET_BLOCK` );
			expect( types.FETCH_TICKET_DETAILS )
				.toBe( `${ PREFIX_TICKETS_STORE }/FETCH_TICKET_DETAILS` );
			expect( types.CANCEL_EDIT_OF_TICKET )
				.toBe( `${ PREFIX_TICKETS_STORE }/CANCEL_EDIT_OF_TICKET` );
			expect( types.SET_TICKET_SOLD )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_SOLD` );
			expect( types.SET_TICKET_AVAILABLE )
				.toBe( `${ PREFIX_TICKETS_STORE }/SET_TICKET_AVAILABLE` );
		} );
	} );
} );


