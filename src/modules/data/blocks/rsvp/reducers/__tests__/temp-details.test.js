/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';
import reducer from '@moderntribe/tickets/data/blocks/rsvp/reducers/temp-details';
import { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/rsvp/reducers/details';

jest.mock( 'moment', () => () => {
	const moment = jest.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Temp details reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the temp title', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempTitle( 'new title' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp description', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempDescription( 'new description' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp capacity', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempCapacity( 20 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp not going responses', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempNotGoingResponses( true ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp start date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartDate( 'January 1, 2018' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp start date input', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartDateInput( 'January 1, 2018' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp start date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPTempStartDateMoment( 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp end date', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndDate( 'January 1, 2018' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp end date input', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndDateInput( 'January 1, 2018' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp end date moment', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setRSVPTempEndDateMoment( 'January 1, 2018' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp start time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartTime( '13:45' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp end time', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndTime( '13:45' ) ) ).toMatchSnapshot();
	} );

	it( 'should set the temp start time input', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempStartTimeInput( '13:45' ) ) )
			.toMatchSnapshot();
	} );

	it( 'should set the temp end time input', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPTempEndTimeInput( '13:45' ) ) )
			.toMatchSnapshot();
	} );
} );
