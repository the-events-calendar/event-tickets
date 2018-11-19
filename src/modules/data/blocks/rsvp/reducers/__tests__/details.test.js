/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';
import reducer, { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/rsvp/reducers/details';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Details reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the title', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTitle( 'new title' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the description', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPDescription( 'new description' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the capacity', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPCapacity( 20 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the not going responses', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPNotGoingResponses( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the start date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPStartDate( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the start date object', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPStartDateObj( new Date( 'January 1, 2018' ) ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the end date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPEndDate( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the end date object', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPEndDateObj( new Date( 'January 1, 2018' ) ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the start time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPStartTime( '13:45' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the end time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPEndTime( '13:45' ) ) ).toMatchSnapshot();
	} );
} );
