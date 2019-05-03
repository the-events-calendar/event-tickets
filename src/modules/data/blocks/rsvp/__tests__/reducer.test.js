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

	it( 'should set the is modal open', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPIsModalOpen( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the going count', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPGoingCount( 10 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the not going count', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPNotGoingCount( 10 ) ) ).toMatchSnapshot();
	} );

	it( 'should set the has attendee info fields', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPHasAttendeeInfoFields( true ) ) ).toMatchSnapshot();
	} );

	it( 'should set the has duration error', () => {
		expect( reducer( DEFAULT_STATE, actions.setRSVPHasDurationError( true ) ) ).toMatchSnapshot();
	} );
} );
