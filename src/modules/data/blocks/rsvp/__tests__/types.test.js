/**
 * Internal dependencies
 */
import { PREFIX_TICKETS_STORE } from '@moderntribe/tickets/data/utils';
import { types } from '@moderntribe/tickets/data/blocks/rsvp';

describe( 'RSVP block types', () => {
	test( 'create RSVP', () => {
		expect( types.CREATE_RSVP )
			.toBe( `${ PREFIX_TICKETS_STORE }/CREATE_RSVP` );
	} );
	test( 'initialize RSVP', () => {
		expect( types.INITIALIZE_RSVP )
			.toBe( `${ PREFIX_TICKETS_STORE }/INITIALIZE_RSVP` );
	} );

	test( 'delete RSVP', () => {
		expect( types.DELETE_RSVP )
			.toBe( `${ PREFIX_TICKETS_STORE }/DELETE_RSVP` );
	} );

	test( 'set RSVP id', () => {
		expect( types.SET_RSVP_ID )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_ID` );
	} );

	test( 'set RSVP settings open', () => {
		expect( types.SET_RSVP_SETTINGS_OPEN )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_SETTINGS_OPEN` );
	} );

	test( 'set RSVP has changes', () => {
		expect( types.SET_RSVP_HAS_CHANGES )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_HAS_CHANGES` );
	} );

	test( 'set RSVP is loading', () => {
		expect( types.SET_RSVP_IS_LOADING )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_IS_LOADING` );
	} );

	test( 'set RSVP is settings loading', () => {
		expect( types.SET_RSVP_IS_SETTINGS_LOADING )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_IS_SETTINGS_LOADING` );
	} );

	test( 'set RSVP going count', () => {
		expect( types.SET_RSVP_GOING_COUNT )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_GOING_COUNT` );
	} );

	test( 'set RSVP not going count', () => {
		expect( types.SET_RSVP_NOT_GOING_COUNT )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_NOT_GOING_COUNT` );
	} );

	test( 'set RSVP title', () => {
		expect( types.SET_RSVP_TITLE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TITLE` );
	} );

	test( 'set RSVP description', () => {
		expect( types.SET_RSVP_DESCRIPTION )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_DESCRIPTION` );
	} );

	test( 'set RSVP capacity', () => {
		expect( types.SET_RSVP_CAPACITY )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_CAPACITY` );
	} );

	test( 'set RSVP not going responses', () => {
		expect( types.SET_RSVP_NOT_GOING_RESPONSES )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_NOT_GOING_RESPONSES` );
	} );

	test( 'set RSVP start date', () => {
		expect( types.SET_RSVP_START_DATE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_START_DATE` );
	} );

	test( 'set RSVP start date object', () => {
		expect( types.SET_RSVP_START_DATE_OBJ )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_START_DATE_OBJ` );
	} );

	test( 'set RSVP end date', () => {
		expect( types.SET_RSVP_END_DATE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_END_DATE` );
	} );

	test( 'set RSVP end date object', () => {
		expect( types.SET_RSVP_END_DATE_OBJ )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_END_DATE_OBJ` );
	} );

	test( 'set RSVP start time', () => {
		expect( types.SET_RSVP_START_TIME )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_START_TIME` );
	} );

	test( 'set RSVP end time', () => {
		expect( types.SET_RSVP_END_TIME )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_END_TIME` );
	} );

	test( 'set RSVP temp title', () => {
		expect( types.SET_RSVP_TEMP_TITLE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_TITLE` );
	} );

	test( 'set RSVP temp description', () => {
		expect( types.SET_RSVP_TEMP_DESCRIPTION )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_DESCRIPTION` );
	} );

	test( 'set RSVP temp capacity', () => {
		expect( types.SET_RSVP_TEMP_CAPACITY )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_CAPACITY` );
	} );

	test( 'set RSVP temp not going responses', () => {
		expect( types.SET_RSVP_TEMP_NOT_GOING_RESPONSES )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_NOT_GOING_RESPONSES` );
	} );

	test( 'set RSVP temp start date', () => {
		expect( types.SET_RSVP_TEMP_START_DATE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_START_DATE` );
	} );

	test( 'set RSVP temp start date object', () => {
		expect( types.SET_RSVP_TEMP_START_DATE_OBJ )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_START_DATE_OBJ` );
	} );

	test( 'set RSVP temp end date', () => {
		expect( types.SET_RSVP_TEMP_END_DATE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_END_DATE` );
	} );

	test( 'set RSVP temp end date object', () => {
		expect( types.SET_RSVP_TEMP_END_DATE_OBJ )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_END_DATE_OBJ` );
	} );

	test( 'set RSVP temp start time', () => {
		expect( types.SET_RSVP_TEMP_START_TIME )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_START_TIME` );
	} );

	test( 'set RSVP temp end time', () => {
		expect( types.SET_RSVP_TEMP_END_TIME )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_END_TIME` );
	} );

	test( 'set RSVP header image', () => {
		expect( types.SET_RSVP_HEADER_IMAGE )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_HEADER_IMAGE` );
	} );

	test( 'set RSVP details', () => {
		expect( types.SET_RSVP_DETAILS )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_DETAILS` );
	} );

	test( 'set RSVP temp details', () => {
		expect( types.SET_RSVP_TEMP_DETAILS )
			.toBe( `${ PREFIX_TICKETS_STORE }/SET_RSVP_TEMP_DETAILS` );
	} );
} );
