/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/rsvp';

describe( 'RSVP block actions', () => {
	describe( 'RSVP actions', () => {
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

		test( 'set RSVP is modal open', () => {
			expect( actions.setRSVPIsModalOpen( true ) ).toMatchSnapshot();
		} );

		test( 'set RSVP going count', () => {
			expect( actions.setRSVPGoingCount( 10 ) ).toMatchSnapshot();
		} );

		test( 'set RSVP not going count', () => {
			expect( actions.setRSVPNotGoingCount( 10 ) ).toMatchSnapshot();
		} );

		test( 'set RSVP has attendee info fields', () => {
			expect( actions.setRSVPHasAttendeeInfoFields( true ) ).toMatchSnapshot();
		} );

		test( 'set RSVP has duration error', () => {
			expect( actions.setRSVPHasDurationError( true ) ).toMatchSnapshot();
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
				startDateInput: 'January 1, 2018',
				startDateMoment: moment( 'January 1, 2018' ),
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endDateInput: 'January 4, 2018',
				endDateMoment: moment( 'January 4, 2018' ),
				endTime: '23:32',
				startTimeInput: '12:34',
				endTimeInput: '23:32',
			} ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp details', () => {
			expect( actions.setRSVPTempDetails( {
				tempTitle: 'title',
				tempDescription: 'description',
				tempCapacity: 20,
				tempNotGoingResponses: true,
				tempStartDate: 'January 1, 2018',
				tempStartDateInput: 'January 1, 2018',
				tempStartDateMoment: moment( 'January 1, 2018' ),
				tempStartTime: '12:34',
				tempEndDate: 'January 4, 2018',
				tempEndDateInput: 'January 4, 2018',
				tempEndDateMoment: moment( 'January 4, 2018' ),
				tempEndTime: '23:32',
				tempStartTimeInput: '12:34',
				tempEndTimeInput: '23:32',
			} ) ).toMatchSnapshot();
		} );
	} );

	describe( 'RSVP details actions', () => {
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

		test( 'set RSVP start date input', () => {
			expect( actions.setRSVPStartDateInput( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP start date moment', () => {
			expect( actions.setRSVPStartDateMoment( moment( 'January 1, 2018' ) ) ).toMatchSnapshot();
		} );

		test( 'set RSVP end date', () => {
			expect( actions.setRSVPEndDate( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP end date input', () => {
			expect( actions.setRSVPEndDateInput( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP end date moment', () => {
			expect( actions.setRSVPEndDateMoment( moment( 'January 1, 2018' ) ) ).toMatchSnapshot();
		} );

		test( 'set RSVP start time', () => {
			expect( actions.setRSVPStartTime( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP end time', () => {
			expect( actions.setRSVPEndTime( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP start time input', () => {
			expect( actions.setRSVPStartTimeInput( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP end time input', () => {
			expect( actions.setRSVPEndTimeInput( '12:34' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'RSVP temp details actions', () => {
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

		test( 'set RSVP temp start date input', () => {
			expect( actions.setRSVPTempStartDateInput( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp start date moment', () => {
			expect( actions.setRSVPTempStartDateMoment( moment( 'January 1, 2018' ) ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp end date', () => {
			expect( actions.setRSVPTempEndDate( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp end date input', () => {
			expect( actions.setRSVPTempEndDateInput( 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp end date moment', () => {
			expect( actions.setRSVPTempEndDateMoment( moment( 'January 1, 2018' ) ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp start time', () => {
			expect( actions.setRSVPTempStartTime( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp end time', () => {
			expect( actions.setRSVPTempEndTime( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp start time input', () => {
			expect( actions.setRSVPTempStartTimeInput( '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set RSVP temp end time input', () => {
			expect( actions.setRSVPTempEndTimeInput( '12:34' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'RSVP thunk & saga actions', () => {
		test( 'create RSVP', () => {
			expect( actions.createRSVP() ).toMatchSnapshot();
		} );

		test( 'initialize RSVP', () => {
			expect( actions.initializeRSVP() ).toMatchSnapshot();
		} );

		test( 'delete RSVP', () => {
			expect( actions.deleteRSVP() ).toMatchSnapshot();
		} );

		test( 'handle RSVP start date', () => {
			expect( actions.handleRSVPStartDate( {} ) ).toMatchSnapshot();
		} );

		test( 'handle RSVP end date', () => {
			expect( actions.handleRSVPEndDate( {} ) ).toMatchSnapshot();
		} );

		test( 'handle RSVP start time', () => {
			expect( actions.handleRSVPStartTime( 1000 ) ).toMatchSnapshot();
		} );

		test( 'handle RSVP end time', () => {
			expect( actions.handleRSVPEndTime( 1000 ) ).toMatchSnapshot();
		} );

		test( 'fetch RSVP header image', () => {
			expect( actions.fetchRSVPHeaderImage( 10 ) ).toMatchSnapshot();
		} );

		test( 'fetch RSVP header image', () => {
			expect( actions.updateRSVPHeaderImage( {} ) ).toMatchSnapshot();
		} );

		test( 'fetch RSVP header image', () => {
			expect( actions.deleteRSVPHeaderImage() ).toMatchSnapshot();
		} );
	} );
} );
