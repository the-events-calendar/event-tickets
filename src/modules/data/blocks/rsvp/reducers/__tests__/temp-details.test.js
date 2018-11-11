/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';
import reducer from '@moderntribe/tickets/data/blocks/rsvp/reducers/temp-details';
import { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/rsvp/reducers/details';


jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Header image reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
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
} );
