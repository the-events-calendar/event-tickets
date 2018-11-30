/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';

describe( 'RSVP block actions', () => {
	test( 'create RSVP', () => {
		expect( actions.createRSVP() ).toMatchSnapshot();
	} );

	test( 'initialize RSVP', () => {
		expect( actions.initializeRSVP() ).toMatchSnapshot();
	} );

	test( 'delete RSVP', () => {
		expect( actions.deleteRSVP() ).toMatchSnapshot();
	} );

	test( 'set RSVP id', () => {
		expect( actions.setRSVPId( 42 ) ).toMatchSnapshot();
	} );

	test( 'set RSVP settings open', () => {
		expect( actions.setRSVPSettingsOpen( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP has changes', () => {
		expect( actions.setRSVPHasChanges( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP is loading', () => {
		expect( actions.setRSVPIsLoading( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP is settings loading', () => {
		expect( actions.setRSVPIsSettingsLoading( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP going count', () => {
		expect( actions.setRSVPGoingCount( 10 ) ).toMatchSnapshot();
	} );

	test( 'set RSVP not going count', () => {
		expect( actions.setRSVPNotGoingCount( 10 ) ).toMatchSnapshot();
	} );

	test( 'set RSVP title', () => {
		expect( actions.setRSVPTitle( 'title' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP description', () => {
		expect( actions.setRSVPDescription( 'description' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP capacity', () => {
		expect( actions.setRSVPCapacity( 20 ) ).toMatchSnapshot();
	} );

	test( 'set RSVP not going responses', () => {
		expect( actions.setRSVPNotGoingResponses( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP start date', () => {
		expect( actions.setRSVPStartDate( 'January 1, 2018' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP start date object', () => {
		expect( actions.setRSVPStartDateObj( new Date( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	test( 'set RSVP end date', () => {
		expect( actions.setRSVPEndDate( 'January 1, 2018' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP end date object', () => {
		expect( actions.setRSVPEndDateObj( new Date( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	test( 'set RSVP start time', () => {
		expect( actions.setRSVPStartTime( '12:34' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP end time', () => {
		expect( actions.setRSVPEndTime( '12:34' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp title', () => {
		expect( actions.setRSVPTempTitle( 'temp title' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp description', () => {
		expect( actions.setRSVPTempDescription( 'temp description' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp capacity', () => {
		expect( actions.setRSVPTempCapacity( 20 ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp not going responses', () => {
		expect( actions.setRSVPTempNotGoingResponses( true ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp start date', () => {
		expect( actions.setRSVPTempStartDate( 'January 1, 2018' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp start date', () => {
		expect( actions.setRSVPTempStartDateObj( new Date( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp end date', () => {
		expect( actions.setRSVPTempEndDate( 'January 1, 2018' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp end date', () => {
		expect( actions.setRSVPTempEndDateObj( new Date( 'January 1, 2018' ) ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp start time', () => {
		expect( actions.setRSVPTempStartTime( '12:34' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp end time', () => {
		expect( actions.setRSVPTempEndTime( '12:34' ) ).toMatchSnapshot();
	} );

	test( 'set RSVP header image', () => {
		expect( actions.setRSVPHeaderImage( {
			id: 42,
			src: 'test-src',
			alt: 'test-alt',
		} ) ).toMatchSnapshot();
	} );

	test( 'set RSVP details', () => {
		expect( actions.setRSVPDetails( {
			title: 'title',
			description: 'description',
			capacity: 20,
			notGoingResponses: true,
			startDate: 'January 1, 2018',
			startDateObj: new Date( 'January 1, 2018' ),
			startTime: '12:34',
			endDate: 'January 4, 2018',
			endDateObj: new Date( 'January 4, 2018' ),
			endTime: '23:32',
	} ) ).toMatchSnapshot();
	} );

	test( 'set RSVP temp details', () => {
		expect( actions.setRSVPTempDetails( {
			tempTitle: 'title',
			tempDescription: 'description',
			tempCapacity: 20,
			tempNotGoingResponses: true,
			tempStartDate: 'January 1, 2018',
			tempStartDateObj: new Date( 'January 1, 2018' ),
			tempStartTime: '12:34',
			tempEndDate: 'January 4, 2018',
			tempEndDateObj: new Date( 'January 4, 2018' ),
			tempEndTime: '23:32',
		} ) ).toMatchSnapshot();
	} );
} );
