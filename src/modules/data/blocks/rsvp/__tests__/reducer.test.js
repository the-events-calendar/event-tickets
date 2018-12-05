/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';
import reducer, { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/rsvp/reducer';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'RSVP block reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the created flag', () => {
		expect( reducer( DEFAULT_STATE, actions.createRSVP() ) ).toMatchSnapshot();
	} );

	it( 'should set the id', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPId( 42 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the settings open', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPSettingsOpen( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the has changes', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPHasChanges( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the is loading', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPIsLoading( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the is settings loading', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPIsSettingsLoading( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the going count', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPGoingCount( 10 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the not going count', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPNotGoingCount( 10 ) ) ).toMatchSnapshot();
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

	it( 'should set the temp title', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempTitle( 'new title' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp description', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempDescription( 'new description' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp capacity', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempCapacity( 20 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp not going responses', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempNotGoingResponses( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp start date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartDate( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp start date object', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPTempStartDateObj( new Date( 'January 1, 2018' ) ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndDate( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp end date object', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPTempEndDate( new Date( 'January 1, 2018' ) ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartTime( '13:45' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp end time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndTime( '13:45' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the header image state', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPHeaderImage( {
			id: 42,
			src: 'test-src',
			alt: 'test-alt',
		} ) ) ).toMatchSnapshot();
	} );
} );
