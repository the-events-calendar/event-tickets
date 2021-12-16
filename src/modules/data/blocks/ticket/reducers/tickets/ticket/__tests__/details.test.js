/**
 * Internal dependencies
 */
import * as actions from '@moderntribe/tickets/data/blocks/ticket/actions';
import reducer, { DEFAULT_STATE } from '../details';

jest.mock( 'moment', () => () => {
	const moment = jest.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Details reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the title', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketTitle( 'block-id', 'new title' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the description', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketDescription( 'block-id', 'new description' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the price', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketPrice( 'block-id', 99 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the sku', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketSku( 'block-id', '12345678' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the iac setting', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketIACSetting( 'block-id', 'none' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the start date', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketStartDate( 'block-id', 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the start date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketStartDateMoment( 'block-id', { type: 'moment' } ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the end date', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketEndDate( 'block-id', 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the end date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketEndDateMoment( 'block-id', { type: 'moment' } ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the start time', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketStartTime( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the end time', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketEndTime( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the start time input', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketStartTimeInput( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the end time input', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketEndTimeInput( 'block-id', '13:45' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the capacity type', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketCapacityType( 'block-id', 'unlimited' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the capacity', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketCapacity( 'block-id', 20 ),
		) ).toMatchSnapshot();
	} );
} );
