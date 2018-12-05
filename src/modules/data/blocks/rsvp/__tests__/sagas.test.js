/**
 * External dependencies
 */
import { takeEvery, put, call, select, all } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * WordPress dependencies
 */
import { select as wpSelect } from '@wordpress/data';

/**
 * Internal Dependencies
 */
import * as types from '../types';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import { moment as momentUtil } from '@moderntribe/common/utils';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

jest.mock( '@wordpress/data', () => ( {
	select: ( key ) => {
		if ( key === 'core/editor' ) {
			return {
				getEditedPostAttribute: ( attr ) => {
					if ( attr === 'date' ) {
						return 'January 1, 2018';
					}
				},
			};
		}
	},
} ) );

describe( 'RSVP block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual(
				takeEvery( [
					types.SET_RSVP_DETAILS,
					types.SET_RSVP_TEMP_DETAILS,
					types.INITIALIZE_RSVP,
					types.HANDLE_RSVP_START_DATE,
					types.HANDLE_RSVP_END_DATE,
					types.HANDLE_RSVP_START_TIME,
					types.HANDLE_RSVP_END_TIME,
					MOVE_TICKET_SUCCESS,
				], sagas.handler )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handlers', () => {
		let action;

		beforeEach( () => {
			action = { type: null };
		} );

		it( 'should set rsvp details', () => {
			action.type = types.SET_RSVP_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setRSVPDetails, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set rsvp temp details', () => {
			action.type = types.SET_RSVP_TEMP_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setRSVPTempDetails, action )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should initialize rsvp', () => {
			action.type = types.INITIALIZE_RSVP;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.initializeRSVP )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start date', () => {
			action.type = types.HANDLE_RSVP_START_DATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartDate, action )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end date', () => {
			action.type = types.HANDLE_RSVP_END_DATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndDate, action )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start time', () => {
			action.type = types.HANDLE_RSVP_START_TIME;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartTimeInput, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartTime, action )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end time', () => {
			action.type = types.HANDLE_RSVP_END_TIME;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndTimeInput, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndTime, action )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should move success ticket', () => {
			action.type = MOVE_TICKET_SUCCESS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPMove )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setRSVPDetails', () => {
		let action;
		beforeEach( () => {
			action = { payload: {
				title: 'title',
				description: 'description',
				capacity: '20',
				notGoingResponses: true,
				startDate: 'January 1, 2018',
				startDateInput: 'January 1, 2018',
				startDateMoment: 'January 1, 2018',
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endDateInput: 'January 4, 2018',
				endDateMoment: 'January 4, 2018',
				endTime: '23:32',
				startTimeInput: '12:34 pm',
				endTimeInput: '11:32 pm',
			} };
		} );

		it( 'should set details state properties', () => {
			const gen = sagas.setRSVPDetails( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setRSVPTitle( 'title' ) ),
					put( actions.setRSVPDescription( 'description' ) ),
					put( actions.setRSVPCapacity( '20' ) ),
					put( actions.setRSVPNotGoingResponses( true ) ),
					put( actions.setRSVPStartDate( 'January 1, 2018' ) ),
					put( actions.setRSVPStartDateInput( 'January 1, 2018' ) ),
					put( actions.setRSVPStartDateMoment( 'January 1, 2018' ) ),
					put( actions.setRSVPStartTime( '12:34' ) ),
					put( actions.setRSVPEndDate( 'January 4, 2018' ) ),
					put( actions.setRSVPEndDateInput( 'January 4, 2018' ) ),
					put( actions.setRSVPEndDateMoment( 'January 4, 2018' ) ),
					put( actions.setRSVPEndTime( '23:32' ) ),
					put( actions.setRSVPStartTimeInput( '12:34 pm' ) ),
					put( actions.setRSVPEndTimeInput( '11:32 pm' ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setRSVPTempDetails', () => {
		let action;
		beforeEach( () => {
			action = { payload: {
				tempTitle: 'title',
				tempDescription: 'description',
				tempCapacity: '20',
				tempNotGoingResponses: true,
				tempStartDate: 'January 1, 2018',
				tempStartDateInput: 'January 1, 2018',
				tempStartDateMoment: 'January 1, 2018',
				tempStartTime: '12:34',
				tempEndDate: 'January 4, 2018',
				tempEndDateInput: 'January 4, 2018',
				tempEndDateMoment: 'January 4, 2018',
				tempEndTime: '23:32',
				tempStartTimeInput: '12:34 pm',
				tempEndTimeInput: '11:32 pm',
			} };
		} );

		it( 'should set temp details state properties', () => {
			const gen = sagas.setRSVPTempDetails( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setRSVPTempTitle( 'title' ) ),
					put( actions.setRSVPTempDescription( 'description' ) ),
					put( actions.setRSVPTempCapacity( '20' ) ),
					put( actions.setRSVPTempNotGoingResponses( true ) ),
					put( actions.setRSVPTempStartDate( 'January 1, 2018' ) ),
					put( actions.setRSVPTempStartDateInput( 'January 1, 2018' ) ),
					put( actions.setRSVPTempStartDateMoment( 'January 1, 2018' ) ),
					put( actions.setRSVPTempStartTime( '12:34' ) ),
					put( actions.setRSVPTempEndDate( 'January 4, 2018' ) ),
					put( actions.setRSVPTempEndDateInput( 'January 4, 2018' ) ),
					put( actions.setRSVPTempEndDateMoment( 'January 4, 2018' ) ),
					put( actions.setRSVPTempEndTime( '23:32' ) ),
					put( actions.setRSVPTempStartTimeInput( '12:34 pm' ) ),
					put( actions.setRSVPTempEndTimeInput( '11:32 pm' ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'initializeRSVP', () => {
		let state;
		beforeEach( () => {
			state = {
				startDate: 'January 1, 2018',
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endTime: '23:32',
			};
			global.tribe = {
				events: {
					data: {
						blocks: {
							datetime: {
								selectors: {
									getStart: jest.fn(),
								},
							},
						},
					},
				},
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should initialize state from datetime block', () => {
			const gen = sagas.initializeRSVP();

			expect( gen.next().value ).toEqual(
				call( wpSelect, 'core/editor' )
			);
			const wpSelectCoreEditor = {
				getEditedPostAttribute: () => {},
			};
			expect( gen.next( wpSelectCoreEditor ).value ).toEqual(
				call( wpSelectCoreEditor.getEditedPostAttribute, 'date' )
			)
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toMoment, state.startDate )
			);
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toDate, state.startDate )
			);
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toDate, state.startDate )
			);
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toDatabaseTime, state.startDate )
			);
			expect( gen.next( state.startTime ).value ).toEqual(
				call( momentUtil.toTime, state.startDate )
			);
			expect( gen.next( state.startTime ).value ).toEqual(
				all( [
					put( actions.setRSVPTempStartDate( state.startDate ) ),
					put( actions.setRSVPTempStartDateInput( state.startDate ) ),
					put( actions.setRSVPTempStartDateMoment( state.startDate ) ),
					put( actions.setRSVPTempStartTime( state.startTime ) ),
					put( actions.setRSVPTempStartTimeInput( state.startTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);
			expect( gen.next( state.endDate ).value ).toEqual(
				call( momentUtil.toMoment, state.endDate )
			);
			expect( gen.next( state.endDate ).value ).toEqual(
				call( momentUtil.toDate, state.endDate )
			);
			expect( gen.next( state.endDate ).value ).toEqual(
				call( momentUtil.toDate, state.endDate )
			);
			expect( gen.next( state.endDate ).value ).toEqual(
				call( momentUtil.toDatabaseTime, state.endDate )
			);
			expect( gen.next( state.endTime ).value ).toEqual(
				call( momentUtil.toTime, state.endDate )
			);
			expect( gen.next( state.endTime ).value ).toEqual(
				all( [
					put( actions.setRSVPTempEndDate( state.endDate ) ),
					put( actions.setRSVPTempEndDateInput( state.endDate ) ),
					put( actions.setRSVPTempEndDateMoment( state.endDate ) ),
					put( actions.setRSVPTempEndTime( state.endTime ) ),
					put( actions.setRSVPTempEndTimeInput( state.endTime ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
