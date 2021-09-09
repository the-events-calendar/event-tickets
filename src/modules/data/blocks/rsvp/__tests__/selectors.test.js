/**
 * Internal dependencies
 */
import { selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/rsvp/reducer';

const state = {
	tickets: {
		blocks: {
			rsvp: DEFAULT_STATE,
		},
	},
};

describe( 'RSVP block selectors', () => {
	it( 'should return the block', () => {
		expect( selectors.getRSVPBlock( state ) )
			.toEqual( DEFAULT_STATE );
	} );

	it( 'should return the id', () => {
		expect( selectors.getRSVPId( state ) )
			.toBe( DEFAULT_STATE.id );
	} );

	it( 'should return the created', () => {
		expect( selectors.getRSVPCreated( state ) )
			.toBe( DEFAULT_STATE.created );
	} );

	it( 'should return the settings open', () => {
		expect( selectors.getRSVPSettingsOpen( state ) )
			.toBe( DEFAULT_STATE.settingsOpen );
	} );

	it( 'should return the has changes', () => {
		expect( selectors.getRSVPHasChanges( state ) )
			.toBe( DEFAULT_STATE.hasChanges );
	} );

	it( 'should return the is loading', () => {
		expect( selectors.getRSVPIsLoading( state ) )
			.toBe( DEFAULT_STATE.isLoading );
	} );

	it( 'should return the is settings loading', () => {
		expect( selectors.getRSVPIsSettingsLoading( state ) )
			.toBe( DEFAULT_STATE.isSettingsLoading );
	} );

	it( 'should return the is modal open', () => {
		expect( selectors.getRSVPIsModalOpen( state ) )
			.toBe( DEFAULT_STATE.isModalOpen );
	} );

	it( 'should return the going count', () => {
		expect( selectors.getRSVPGoingCount( state ) )
			.toBe( DEFAULT_STATE.goingCount );
	} );

	it( 'should return the not going count', () => {
		expect( selectors.getRSVPNotGoingCount( state ) )
			.toBe( DEFAULT_STATE.notGoingCount );
	} );

	it( 'should return the has attendee info fields', () => {
		expect( selectors.getRSVPHasAttendeeInfoFields( state ) )
			.toBe( DEFAULT_STATE.hasAttendeeInfoFields );
	} );

	it( 'should return the has duration error', () => {
		expect( selectors.getRSVPHasDurationError( state ) )
			.toBe( DEFAULT_STATE.hasDurationError );
	} );

	it( 'should return the details object', () => {
		expect( selectors.getRSVPDetails( state ) )
			.toBe( DEFAULT_STATE.details );
	} );

	it( 'should return the title', () => {
		expect( selectors.getRSVPTitle( state ) )
			.toBe( DEFAULT_STATE.details.title );
	} );

	it( 'should return the description', () => {
		expect( selectors.getRSVPDescription( state ) )
			.toBe( DEFAULT_STATE.details.description );
	} );

	it( 'should return the capacity', () => {
		expect( selectors.getRSVPCapacity( state ) )
			.toBe( DEFAULT_STATE.details.capacity );
	} );

	it( 'should return the total available tickets', () => {
		expect( selectors.getRSVPAvailable( state ) ).toBe( -1 );
	} );

	it( 'should return the not going responses', () => {
		expect( selectors.getRSVPNotGoingResponses( state ) )
			.toBe( DEFAULT_STATE.details.notGoingResponses );
	} );

	it( 'should return the start date', () => {
		expect( selectors.getRSVPStartDate( state ) )
			.toBe( DEFAULT_STATE.details.startDate );
	} );

	it( 'should return the start date input', () => {
		expect( selectors.getRSVPStartDateInput( state ) )
			.toBe( DEFAULT_STATE.details.startDateInput );
	} );

	it( 'should return the start date moment', () => {
		expect( selectors.getRSVPStartDateMoment( state ) )
			.toBe( DEFAULT_STATE.details.startDateMoment );
	} );

	it( 'should return the start time', () => {
		expect( selectors.getRSVPStartTime( state ) )
			.toBe( DEFAULT_STATE.details.startTime );
	} );

	it( 'should return the start time no seconds', () => {
		expect( selectors.getRSVPStartTimeNoSeconds( state ) )
			.toBe( DEFAULT_STATE.details.startTime.slice( 0, -3 ) );
	} );

	it( 'should return the end date', () => {
		expect( selectors.getRSVPEndDate( state ) )
			.toBe( DEFAULT_STATE.details.endDate );
	} );

	it( 'should return the end date input', () => {
		expect( selectors.getRSVPEndDateInput( state ) )
			.toBe( DEFAULT_STATE.details.endDateInput );
	} );

	it( 'should return the end date moment', () => {
		expect( selectors.getRSVPEndDateMoment( state ) )
			.toBe( DEFAULT_STATE.details.endDateMoment );
	} );

	it( 'should return the end time', () => {
		expect( selectors.getRSVPEndTime( state ) )
			.toBe( DEFAULT_STATE.details.endTime );
	} );

	it( 'should return the end time no seconds', () => {
		expect( selectors.getRSVPEndTimeNoSeconds( state ) )
			.toBe( DEFAULT_STATE.details.endTime.slice( 0, -3 ) );
	} );

	it( 'should return the start time input', () => {
		expect( selectors.getRSVPStartTimeInput( state ) )
			.toBe( DEFAULT_STATE.details.startTimeInput );
	} );

	it( 'should return the end time input', () => {
		expect( selectors.getRSVPEndTimeInput( state ) )
			.toBe( DEFAULT_STATE.details.endTimeInput );
	} );

	it( 'should return the temp title', () => {
		expect( selectors.getRSVPTempTitle( state ) )
			.toBe( DEFAULT_STATE.tempDetails.title );
	} );

	it( 'should return the temp description', () => {
		expect( selectors.getRSVPTempDescription( state ) )
			.toBe( DEFAULT_STATE.tempDetails.description );
	} );

	it( 'should return the temp capacity', () => {
		expect( selectors.getRSVPTempCapacity( state ) )
			.toBe( DEFAULT_STATE.tempDetails.capacity );
	} );

	it( 'should return the temp not going responses', () => {
		expect( selectors.getRSVPTempNotGoingResponses( state ) )
			.toBe( DEFAULT_STATE.tempDetails.notGoingResponses );
	} );

	it( 'should return the temp start date', () => {
		expect( selectors.getRSVPTempStartDate( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startDate );
	} );

	it( 'should return the temp start date input', () => {
		expect( selectors.getRSVPTempStartDateInput( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startDateInput );
	} );

	it( 'should return the temp start date moment', () => {
		expect( selectors.getRSVPTempStartDateMoment( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startDateMoment );
	} );

	it( 'should return the temp start time', () => {
		expect( selectors.getRSVPTempStartTime( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startTime );
	} );

	it( 'should return the temp start time no seconds', () => {
		expect( selectors.getRSVPTempStartTimeNoSeconds( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startTime.slice( 0, -3 ) );
	} );

	it( 'should return the temp end date', () => {
		expect( selectors.getRSVPTempEndDate( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endDate );
	} );

	it( 'should return the temp end date input', () => {
		expect( selectors.getRSVPTempEndDateInput( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endDateInput );
	} );

	it( 'should return the temp end date moment', () => {
		expect( selectors.getRSVPTempEndDateMoment( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endDateMoment );
	} );

	it( 'should return the temp end time', () => {
		expect( selectors.getRSVPTempEndTime( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endTime );
	} );

	it( 'should return the temp end time no seconds', () => {
		expect( selectors.getRSVPTempEndTimeNoSeconds( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endTime.slice( 0, -3 ) );
	} );

	it( 'should return the temp start time input', () => {
		expect( selectors.getRSVPTempStartTimeInput( state ) )
			.toBe( DEFAULT_STATE.tempDetails.startTimeInput );
	} );

	it( 'should return the temp end time input', () => {
		expect( selectors.getRSVPTempEndTimeInput( state ) )
			.toBe( DEFAULT_STATE.tempDetails.endTimeInput );
	} );

	it( 'should return the header image object', () => {
		expect( selectors.getRSVPHeaderImage( state ) )
			.toBe( DEFAULT_STATE.headerImage );
	} );

	it( 'should return the header image id', () => {
		expect( selectors.getRSVPHeaderImageId( state ) )
			.toBe( DEFAULT_STATE.headerImage.id );
	} );

	it( 'should return the header image src', () => {
		expect( selectors.getRSVPHeaderImageSrc( state ) )
			.toBe( DEFAULT_STATE.headerImage.src );
	} );

	it( 'should return the header image alt', () => {
		expect( selectors.getRSVPHeaderImageAlt( state ) )
			.toBe( DEFAULT_STATE.headerImage.alt );
	} );
} );
