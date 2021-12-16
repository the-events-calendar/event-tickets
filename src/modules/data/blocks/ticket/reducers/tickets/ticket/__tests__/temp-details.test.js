/**
 * Internal dependencies
 */
import * as actions from '@moderntribe/tickets/data/blocks/ticket/actions';
import reducer, { DEFAULT_STATE } from '../temp-details';

jest.mock( 'moment', () => () => {
	const moment = jest.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Temp details reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the temp title', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempTitle( 'block-id', 'new title' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp description', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempDescription( 'block-id', 'new description' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp price', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempPrice( 'block-id', 99 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp sku', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempSku( 'block-id', '12345678' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp iac setting', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempIACSetting( 'block-id', 'none' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start date', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempStartDate( 'block-id', 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempStartDateMoment( 'block-id', { type: 'moment' } ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end date', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempEndDate( 'block-id', 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempEndDateMoment( 'block-id', { type: 'moment' } ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start time', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempStartTime( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end time', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempEndTime( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start time input', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempStartTimeInput( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end time input', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempEndTimeInput( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp capacity type', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempCapacityType( 'block-id', 'unlimited' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp capacity', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTempCapacity( 'block-id', 20 ),
		) ).toMatchSnapshot();
	} );
} );
