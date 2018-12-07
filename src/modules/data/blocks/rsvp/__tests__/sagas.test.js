/**
 * External dependencies
 */
import { takeEvery, put, call, select, all } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * WordPress dependencies
 */
import {
	dispatch as wpDispatch,
	select as wpSelect,
} from '@wordpress/data';

/**
 * Internal Dependencies
 */
import * as types from '../types';
import * as actions from '../actions';
import * as selectors from '../selectors';
import watchers, * as sagas from '../sagas';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import {
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

jest.mock( '@wordpress/data', () => ( {
	dispatch: ( key ) => {
		if ( key === 'core/editor' ) {
			return {
				removeBlocks: () => {},
			};
		}
	},
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
				call( sagas.handleRSVPStartTime, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartTimeInput, action )
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
				call( sagas.handleRSVPEndTime, action )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndTimeInput, action )
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

			expect( JSON.stringify( gen.next().value ) ).toEqual(
				JSON.stringify(
					call( [ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ], 'date' )
				)
			);
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
					put( actions.setRSVPStartDate( state.startDate ) ),
					put( actions.setRSVPStartDateInput( state.startDate ) ),
					put( actions.setRSVPStartDateMoment( state.startDate ) ),
					put( actions.setRSVPStartTime( state.startTime ) ),
					put( actions.setRSVPStartTimeInput( state.startTime ) ),
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
					put( actions.setRSVPEndDate( state.endDate ) ),
					put( actions.setRSVPEndDateInput( state.endDate ) ),
					put( actions.setRSVPEndDateMoment( state.endDate ) ),
					put( actions.setRSVPEndTime( state.endTime ) ),
					put( actions.setRSVPEndTimeInput( state.endTime ) ),
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

	describe( 'handleRSVPStartDate', () => {
		let action;

		beforeEach( () => {
			action = {
				payload: {
					date: undefined,
					dayPickerInput: {
						state: {
							value: '',
						}
					}
				}
			}
		} );

		it( 'should handle undefined rsvp start date', () => {
			const gen = sagas.handleRSVPStartDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setRSVPTempStartDate( '' ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateInput( action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateMoment( undefined ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleRSVPStartDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setRSVPTempStartDate( action.payload.date ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateInput( action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateMoment( action.payload.date ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPEndDate', () => {
		let action;

		beforeEach( () => {
			action = {
				payload: {
					date: undefined,
					dayPickerInput: {
						state: {
							value: '',
						}
					}
				}
			}
		} );

		it( 'should handle undefined rsvp end date', () => {
			const gen = sagas.handleRSVPEndDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setRSVPTempEndDate( '' ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateInput( action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateMoment( undefined ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleRSVPEndDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date )
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setRSVPTempEndDate( action.payload.date ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateInput( action.payload.dayPickerInput.state.value ) )
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateMoment( action.payload.date ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPStartTime', () => {
		it( 'should handle rsvp start time', () => {
			const action = {
				payload: {
					seconds: 3600,
				},
			};
			const startTime = '01:00';
			const gen = sagas.handleRSVPStartTime( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( startTime ).value ).toEqual(
				put( actions.setRSVPTempStartTime( `${ startTime }:00` ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPStartTimeInput', () => {
		it( 'should handle rsvp start time input', () => {
			const startTimeInput = '01:00';
			const action = {
				payload: {
					seconds: 3600,
				},
			};
			const gen = sagas.handleRSVPStartTimeInput( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, startTimeInput, momentUtil.TIME_FORMAT, false )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toTime, startTimeInput )
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				put( actions.setRSVPTempStartTimeInput( startTimeInput ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPEndTime', () => {
		it( 'should handle rsvp end time', () => {
			const action = {
				payload: {
					seconds: 3600,
				},
			};
			const endTime = '01:00';
			const gen = sagas.handleRSVPEndTime( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( endTime ).value ).toEqual(
				put( actions.setRSVPTempEndTime( `${ endTime }:00` ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPEndTimeInput', () => {
		it( 'should handle rsvp end time input', () => {
			const endTimeInput = '01:00';
			const action = {
				payload: {
					seconds: 3600,
				},
			};
			const gen = sagas.handleRSVPEndTimeInput( action );
			expect( gen.next().value ).toEqual(
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM )
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, endTimeInput, momentUtil.TIME_FORMAT, false )
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				call( momentUtil.toTime, endTimeInput )
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				put( actions.setRSVPTempEndTimeInput( endTimeInput ) )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPMove', () => {
		it( 'should handle rsvp move', () => {
			const gen = cloneableGenerator( sagas.handleRSVPMove )();
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPId )
			);
			expect( gen.next( 42 ).value ).toEqual(
				select( moveSelectors.getModalTicketId )
			);

			const clone1 = gen.clone();
			expect( clone1.next( 42 ).value ).toEqual(
				select( moveSelectors.getModalBlockId )
			);
			expect( clone1.next( 'modern-tribe' ).value ).toEqual(
				put( actions.deleteRSVP() )
			);
			expect( JSON.stringify( clone1.next().value ) ).toEqual(
				JSON.stringify(
					call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ 'modern-tribe' ] )
				)
			);
			expect( clone1.next().done ).toEqual( true );

			const clone2 = gen.clone();
			expect( clone2.next( 24 ).done ).toEqual( true );
		} );
	} );
} );
