/**
 * External dependencies
 */
import { takeEvery, put, call, select, all, fork, take } from 'redux-saga/effects';
import { noop } from 'lodash';

/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch } from '@wordpress/data';

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
import {
	isTribeEventPostType,
	createDates,
} from '@moderntribe/tickets/data/shared/sagas';

function mock() {
	return {
		select: ( key ) => {
			if ( key === 'core/editor' ) {
				return {
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'date' ) {
							return 'January 1, 2018';
						}
					},
					getCurrentPostId: () => 10,
					getCurrentPostType: () => 'tribe_events',
				};
			}
			if ( key === 'core' ) {
				return {
					getPostType: () => ( {
						rest_base: 'tribe_events',
					} ),
				};
			}
		},
		subscribe: jest.fn( () => noop ),
		dispatch: jest.fn( () => ( {
			removeBlocks: noop,
		} ) ),
	};
}
jest.mock( '@wordpress/data', () => mock() );

describe( 'RSVP shared block sagas', () => {
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
				], sagas.handler ),
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.handleEventStartDateChanges ),
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.setNonEventPostTypeEndDate ),
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
				call( sagas.setRSVPDetails, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set rsvp temp details', () => {
			action.type = types.SET_RSVP_TEMP_DETAILS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.setRSVPTempDetails, action ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should initialize rsvp', () => {
			action.type = types.INITIALIZE_RSVP;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.initializeRSVP ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start date', () => {
			action.type = types.HANDLE_RSVP_START_DATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartDate, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPDurationError ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end date', () => {
			action.type = types.HANDLE_RSVP_END_DATE;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndDate, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPDurationError ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start time', () => {
			action.type = types.HANDLE_RSVP_START_TIME;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartTime, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPStartTimeInput, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPDurationError ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end time', () => {
			action.type = types.HANDLE_RSVP_END_TIME;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndTime, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPEndTimeInput, action ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPDurationError ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPHasChanges( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
		it( 'should move success ticket', () => {
			action.type = MOVE_TICKET_SUCCESS;
			const gen = sagas.handler( action );
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPMove ),
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
				] ),
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
				] ),
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
			global.window = global.window || {};
			window.tec = {
				events: {
					app: {
						main: {
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
					},
				},
			};
		} );

		afterEach( () => {
			delete window.tec;
		} );

		it( 'should initialize state from datetime block', () => {
			const gen = sagas.initializeRSVP();

			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( state.startDate ).value ).toMatchSnapshot();

			expect( gen.next( {
				moment: state.startDate,
				date: state.startDate,
				dateInput: state.startDate,
				time: state.startTime,
				timeInput: state.startTime,
			} ).value ).toEqual(
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);
			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);
			expect( gen.next( state.endDate ).value ).toMatchSnapshot();
			expect( gen.next( {
				moment: state.endDate,
				date: state.endDate,
				dateInput: state.endDate,
				time: state.endTime,
				timeInput: state.endTime,
			} ).value ).toEqual(
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
				] ),
			);
			expect( gen.next().value ).toEqual(
				call( sagas.handleRSVPDurationError ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'syncRSVPSaleEndWithEventStart', () => {
		let prevDate, momentMock;
		beforeEach( () => {
			prevDate = '2018-01-01 00:00:00';
			global.window = global.window || {};
			window.tec = {
				events: {
					app: {
						main: {
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
					},
				},
			};
			momentMock = {
				local: jest.fn(),
				isSame: jest.fn(),
				format: jest.fn(),
			};
		} );

		afterEach( () => {
			delete window.tec;
		} );

		it( 'should not sync', () => {
			const gen = sagas.syncRSVPSaleEndWithEventStart( prevDate );
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getRSVPEndDateMoment ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( createDates, prevDate ),
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( false ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should sync', () => {
			const gen = sagas.syncRSVPSaleEndWithEventStart( prevDate );
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getRSVPEndDateMoment ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( createDates, prevDate ),
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();

			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);
			expect( gen.next( '2018-02-02 02:00:00' ).value ).toEqual(
				call( createDates, '2018-02-02 02:00:00' ),
			);

			expect( gen.next( {
				moment: '2018-02-02',
				date: '2018-02-02',
				dateInput: '2018-02-02',
				time: '02:00:00',
				timeInput: '02:00:00',
			} ).value ).toMatchSnapshot();

			expect( gen.next().value ).toEqual(
				put( { type: types.RSVP_SALE_END_SYNCED_TO_EVENT_START } ),
			);
		} );
	} );

	describe( 'handleRSVPDurationError', () => {
		it( 'should set has duration error to true if start or end moment is invalid', () => {
			const gen = sagas.handleRSVPDurationError();
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempStartDateMoment ),
			);
			expect( gen.next( undefined ).value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( undefined ).value ).toEqual(
				put( actions.setRSVPHasDurationError( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set thas duration error to true if start date time is after end date time', () => {
			const START_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const END_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const START_TIME = '12:00:00';
			const END_TIME = '13:00:00';
			const START_TIME_SECONDS = 43200;
			const END_TIME_SECONDS = 46800;
			const gen = sagas.handleRSVPDurationError();
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempStartDateMoment ),
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				select( selectors.getRSVPTempStartTime ),
			);
			expect( gen.next( START_TIME ).value ).toEqual(
				select( selectors.getRSVPTempEndTime ),
			);
			expect( gen.next( END_TIME ).value ).toEqual(
				call( timeUtil.toSeconds, START_TIME, timeUtil.TIME_FORMAT_HH_MM_SS ),
			);
			expect( gen.next( START_TIME_SECONDS ).value ).toEqual(
				call( timeUtil.toSeconds, END_TIME, timeUtil.TIME_FORMAT_HH_MM_SS ),
			);
			expect( gen.next( END_TIME_SECONDS ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, START_DATE_MOMENT.clone(), START_TIME_SECONDS ),
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, END_DATE_MOMENT.clone(), END_TIME_SECONDS ),
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				call( [ START_DATE_MOMENT, 'isSameOrAfter' ], END_DATE_MOMENT ),
			);
			expect( gen.next( true ).value ).toEqual(
				put( actions.setRSVPHasDurationError( true ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should set thas duration error to false if start date time is before end date time', () => { // eslint-disable-line max-len
			const START_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const END_DATE_MOMENT = {
				clone: () => {},
				isSameOrAfter: () => {},
			};
			const START_TIME = '12:00:00';
			const END_TIME = '13:00:00';
			const START_TIME_SECONDS = 43200;
			const END_TIME_SECONDS = 46800;
			const gen = sagas.handleRSVPDurationError();
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempStartDateMoment ),
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				select( selectors.getRSVPTempStartTime ),
			);
			expect( gen.next( START_TIME ).value ).toEqual(
				select( selectors.getRSVPTempEndTime ),
			);
			expect( gen.next( END_TIME ).value ).toEqual(
				call( timeUtil.toSeconds, START_TIME, timeUtil.TIME_FORMAT_HH_MM_SS ),
			);
			expect( gen.next( START_TIME_SECONDS ).value ).toEqual(
				call( timeUtil.toSeconds, END_TIME, timeUtil.TIME_FORMAT_HH_MM_SS ),
			);
			expect( gen.next( END_TIME_SECONDS ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, START_DATE_MOMENT.clone(), START_TIME_SECONDS ),
			);
			expect( gen.next( START_DATE_MOMENT ).value ).toEqual(
				call( momentUtil.setTimeInSeconds, END_DATE_MOMENT.clone(), END_TIME_SECONDS ),
			);
			expect( gen.next( END_DATE_MOMENT ).value ).toEqual(
				call( [ START_DATE_MOMENT, 'isSameOrAfter' ], END_DATE_MOMENT ),
			);
			expect( gen.next( false ).value ).toEqual(
				put( actions.setRSVPHasDurationError( false ) ),
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
						},
					},
				},
			};
		} );

		it( 'should handle undefined rsvp start date', () => {
			const gen = sagas.handleRSVPStartDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setRSVPTempStartDate( '' ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateInput( action.payload.dayPickerInput ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateMoment( undefined ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp start date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleRSVPStartDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date ),
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date ),
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setRSVPTempStartDate( action.payload.date ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateInput( action.payload.dayPickerInput ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempStartDateMoment( action.payload.date ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleEventStartDateChanges', () => {
		beforeEach( () => {
			global.window = global.window || {};
			window.tec = {
				events: {
					app: {
						main: {
							data: {
								blocks: {
									datetime: {
										selectors: {
											getStart: jest.fn(),
										},
										types: {
											SET_START_DATE_TIME: 'SET_START_DATE_TIME',
											SET_START_TIME: 'SET_START_TIME',
										},
									},
								},
							},
						},
					},
				},
			};
		} );

		afterEach( () => {
			delete window.tec;
		} );

		it( 'should handle start time changes', () => {
			const gen = sagas.handleEventStartDateChanges();

			expect( gen.next( true ).value ).toEqual(
				take( [ types.INITIALIZE_RSVP, types.SET_RSVP_DETAILS ] ),
			);

			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);

			expect( gen.next( true ).value ).toEqual(
				select( window.tec.events.app.main.data.blocks.datetime.selectors.getStart ),
			);

			expect( gen.next( '2018-01-01 12:00:00' ).value ).toEqual(
				take( [ 'SET_START_DATE_TIME', 'SET_START_TIME' ] ),
			);

			expect( gen.next().value ).toEqual(
				fork( sagas.syncRSVPSaleEndWithEventStart, '2018-01-01 12:00:00' ),
			);

			expect( gen.next().done ).toEqual( false );
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
						},
					},
				},
			};
		} );

		it( 'should handle undefined rsvp end date', () => {
			const gen = sagas.handleRSVPEndDate( action );
			expect( gen.next().value ).toEqual( undefined );
			expect( gen.next( undefined ).value ).toEqual( '' );
			expect( gen.next( '' ).value ).toEqual(
				put( actions.setRSVPTempEndDate( '' ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateInput( action.payload.dayPickerInput ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateMoment( undefined ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should handle rsvp end date', () => {
			action.payload.date = 'January 1, 2018';
			action.payload.dayPickerInput.state.value = 'January 1, 2018';
			const gen = sagas.handleRSVPEndDate( action );
			expect( gen.next().value ).toEqual(
				call( momentUtil.toMoment, action.payload.date ),
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				call( momentUtil.toDatabaseDate, action.payload.date ),
			);
			expect( gen.next( action.payload.date ).value ).toEqual(
				put( actions.setRSVPTempEndDate( action.payload.date ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateInput( action.payload.dayPickerInput ) ),
			);
			expect( gen.next().value ).toEqual(
				put( actions.setRSVPTempEndDateMoment( action.payload.date ) ),
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
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM ),
			);
			expect( gen.next( startTime ).value ).toEqual(
				put( actions.setRSVPTempStartTime( `${ startTime }:00` ) ),
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
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM ),
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, startTimeInput, momentUtil.TIME_FORMAT, false ),
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				call( momentUtil.toTime, startTimeInput ),
			);
			expect( gen.next( startTimeInput ).value ).toEqual(
				put( actions.setRSVPTempStartTimeInput( startTimeInput ) ),
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
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM ),
			);
			expect( gen.next( endTime ).value ).toEqual(
				put( actions.setRSVPTempEndTime( `${ endTime }:00` ) ),
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
				call( timeUtil.fromSeconds, action.payload.seconds, timeUtil.TIME_FORMAT_HH_MM ),
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				call( momentUtil.toMoment, endTimeInput, momentUtil.TIME_FORMAT, false ),
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				call( momentUtil.toTime, endTimeInput ),
			);
			expect( gen.next( endTimeInput ).value ).toEqual(
				put( actions.setRSVPTempEndTimeInput( endTimeInput ) ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'handleRSVPMove', () => {
		it( 'should handle move', () => {
			const gen = sagas.handleRSVPMove();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPId ),
			);
			expect( gen.next( 1 ).value ).toEqual(
				select( moveSelectors.getModalTicketId ),
			);
			expect( gen.next( 1 ).value ).toEqual(
				select( moveSelectors.getModalClientId ),
			);
			expect( gen.next( '111111' ).value ).toEqual(
				put( actions.deleteRSVP() ),
			);
			expect( gen.next().value ).toEqual(
				call( [ wpDispatch( 'core/block-editor' ), 'removeBlocks' ], [ '111111' ] ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setNonEventPostTypeEndDate', () => {
		it( 'shoud exit on non-events', () => {
			const gen = sagas.setNonEventPostTypeEndDate();

			expect( gen.next().value ).toEqual(
				take( [ types.INITIALIZE_RSVP ] ),
			);

			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);

			expect( gen.next( true ).done ).toEqual( true );
		} );

		it( 'should set end date', () => {
			const gen = sagas.setNonEventPostTypeEndDate();
			const momentMock = {
				clone: jest.fn(),
				add: jest.fn(),
				toDate: jest.fn(),
			};

			expect( gen.next().value ).toEqual(
				take( [ types.INITIALIZE_RSVP ] ),
			);
			expect( gen.next().value ).toEqual(
				call( isTribeEventPostType ),
			);
			expect( gen.next( false ).value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( [ momentMock, 'clone' ] ),
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( createDates, momentMock.toDate() ),
			);
			expect( gen.next( {
				date: '2018-01-01',
				dateInput: '2018-01-01',
				moment: '2018-01-01',
				time: '12:00:00',
			} ).value ).toMatchSnapshot();

			expect( gen.next().done ).toEqual( true );
		} );
	} );

} );
