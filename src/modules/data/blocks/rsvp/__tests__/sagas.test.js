/**
 * External dependencies
 */
import { select as wpSelect, dispatch as wpDispatch, subscribe } from '@wordpress/data';
import { takeEvery, put, call, select, all, fork, take } from 'redux-saga/effects';
import { cloneableGenerator, createMockTask } from 'redux-saga/utils';
import { noop } from 'lodash';

/**
 * Internal Dependencies
 */
import * as types from '../types';
import * as actions from '../actions';
import * as selectors from '../selectors';
import { updateRSVP } from '../thunks';
import watchers, * as sagas from '../sagas';
import { MOVE_TICKET_SUCCESS } from '@moderntribe/tickets/data/shared/move/types';
import { moment as momentUtil, globals } from '@moderntribe/common/utils';
import * as moveSelectors from '@moderntribe/tickets/data/shared/move/selectors';

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

describe( 'RSVP block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_RSVP_DETAILS, sagas.setRSVPDetails ),
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_RSVP_TEMP_DETAILS, sagas.setRSVPTempDetails ),
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.INITIALIZE_RSVP, sagas.initializeRSVP ),
			);
			expect( gen.next().value ).toEqual(
				takeEvery( MOVE_TICKET_SUCCESS, sagas.handleRSVPMove )
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.handleEventStartDateChanges ),
			);
			expect( gen.next().value ).toEqual(
				fork( sagas.setNonEventPostTypeEndDate )
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
			} };
		} );

		it( 'should set details state properties', () => {
			const gen = cloneableGenerator( sagas.setRSVPDetails )( action );
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
			} };
		} );

		it( 'should set temp details state properties', () => {
			const gen = cloneableGenerator( sagas.setRSVPTempDetails )( action );
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
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'createDates', () => {
		const date = '2018-01-01 00:00:00';
		it( 'should create dates when no format', () => {
			const gen = sagas.createDates( date );

			expect( gen.next().value ).toEqual(
				call( [ globals, 'tecDateSettings' ] )
			);

			expect( gen.next( { datepickerFormat: false } ).value ).toEqual(
				call( momentUtil.toMoment, date )
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {} )
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {} )
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toDatabaseTime, {} )
			);

			expect( gen.next().done ).toEqual( true );
		} );
		it( 'should create dates with datepicker format', () => {
			const gen = sagas.createDates( date );

			expect( gen.next().value ).toEqual(
				call( [ globals, 'tecDateSettings' ] )
			);

			expect( gen.next( { datepickerFormat: true } ).value ).toEqual(
				call( momentUtil.toMoment, date )
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {} )
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {}, true )
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toDatabaseTime, {} )
			);

			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'isTribeEventPostType', () => {
		it( 'should be event', () => {
			const gen = sagas.isTribeEventPostType();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( 'tribe_events' ).value ).toEqual( true );
		} );
		it( 'should not be event', () => {
			const gen = sagas.isTribeEventPostType();
			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( 'no' ).value ).toEqual( false );
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

			expect( gen.next().value ).toMatchSnapshot();
			expect( gen.next( state.startDate ).value ).toMatchSnapshot();

			expect( gen.next( {
				moment: state.startDate,
				date: state.startDate,
				dateInput: state.startDate,
				time: state.startTime,
			} ).value ).toEqual(
				all( [
					put( actions.setRSVPTempStartDate( state.startDate ) ),
					put( actions.setRSVPTempStartDateInput( state.startDate ) ),
					put( actions.setRSVPTempStartDateMoment( state.startDate ) ),
					put( actions.setRSVPTempStartTime( state.startTime ) ),
				] )
			);
			expect( gen.next().value ).toEqual(
				call( sagas.isTribeEventPostType )
			);
			expect( gen.next( true ).value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);
			expect( gen.next( state.endDate ).value ).toMatchSnapshot();
			expect( gen.next( {
				moment: state.endDate,
				date: state.endDate,
				dateInput: state.endDate,
				time: state.endTime,
			} ).value ).toEqual(
				all( [
					put( actions.setRSVPTempEndDate( state.endDate ) ),
					put( actions.setRSVPTempEndDateInput( state.endDate ) ),
					put( actions.setRSVPTempEndDateMoment( state.endDate ) ),
					put( actions.setRSVPTempEndTime( state.endTime ) ),
					put( actions.setRSVPEndDate( state.endDate ) ),
					put( actions.setRSVPEndDateInput( state.endDate ) ),
					put( actions.setRSVPEndDateMoment( state.endDate ) ),
					put( actions.setRSVPEndTime( state.endTime ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'syncRSVPSaleEndWithEventStart', () => {
		let prevDate, state, momentMock;
		beforeEach( () => {
			prevDate = '2018-01-01 00:00:00';
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
			momentMock = {
				local: jest.fn(),
				isSame: jest.fn(),
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should not sync', () => {
			const gen = sagas.syncRSVPSaleEndWithEventStart( prevDate );
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getRSVPEndDateMoment )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( sagas.createDates, prevDate )
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( false ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next().done ).toEqual( true );
		} );

		it( 'should sync', () => {
			const gen = sagas.syncRSVPSaleEndWithEventStart( prevDate );
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				select( selectors.getRSVPEndDateMoment )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( sagas.createDates, prevDate )
			);
			expect( gen.next( { moment: momentMock } ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next( true ).value ).toMatchSnapshot();

			expect( gen.next( true ).value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);
			expect( gen.next( '2018-02-02 02:00:00' ).value ).toEqual(
				call( sagas.createDates, '2018-02-02 02:00:00' )
			);

			expect( gen.next( {
				moment: '2018-02-02',
				date: '2018-02-02',
				dateInput: '2018-02-02',
				time: '02:00:00',
			} ).value ).toMatchSnapshot();

			expect( gen.next().value ).toEqual(
				fork( sagas.saveRSVPWithPostSave )
			);

			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'createWPEditorSavingChannel', () => {
		it( 'should create channel', () => {
			expect( sagas.createWPEditorSavingChannel() ).toMatchSnapshot();
		} );
	} );

	describe( 'saveRSVPWithPostSave', () => {
		let channel;

		beforeEach( () => {
			channel = { name, take: jest.fn(), close: jest.fn() };
		} );

		it( 'should update when channel saves', () => {
			const gen = sagas.saveRSVPWithPostSave();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPCreated )
			);

			expect( gen.next( true ).value ).toEqual(
				call( sagas.createWPEditorSavingChannel )
			);

			expect( gen.next( channel ).value ).toEqual(
				take( channel )
			);
			expect( gen.next( true ).value ).toMatchSnapshot();
			expect( gen.next( {} ).value ).toMatchSnapshot();

			expect( gen.next().value ).toEqual(
				call( [ channel, 'close' ] )
			);

			expect( gen.next().done ).toEqual( true );
		} );
		it( 'should do nothing', () => {
			const gen = sagas.saveRSVPWithPostSave();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPCreated )
			);

			expect( gen.next( false ).done ).toEqual( true );
		} );
	} );

	describe( 'handleEventStartDateChanges', () => {
		beforeEach( () => {
			global.tribe = {
				events: {
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
			};
		} );

		afterEach( () => {
			delete global.tribe;
		} );

		it( 'should handle start time changes', () => {
			const gen = sagas.handleEventStartDateChanges();

			expect( gen.next().value ).toEqual(
				call( sagas.isTribeEventPostType )
			);

			expect( gen.next( true ).value ).toEqual(
				take( [ types.INITIALIZE_RSVP, types.SET_RSVP_DETAILS ] )
			);

			expect( gen.next( true ).value ).toEqual(
				select( global.tribe.events.data.blocks.datetime.selectors.getStart )
			);

			expect( gen.next( '2018-01-01 12:00:00' ).value ).toEqual(
				take( [ 'SET_START_DATE_TIME', 'SET_START_TIME' ] )
			);

			expect( gen.next().value ).toEqual(
				fork( sagas.syncRSVPSaleEndWithEventStart, '2018-01-01 12:00:00' )
			);

			expect( gen.next().done ).toEqual( false );
		} );
	} );

	describe( 'handleRSVPMove', () => {
		it( 'should handle move', () => {
			const gen = sagas.handleRSVPMove();

			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPId )
			);
			expect( gen.next( 1 ).value ).toEqual(
				select( moveSelectors.getModalTicketId )
			);
			expect( gen.next( 1 ).value ).toEqual(
				select( moveSelectors.getModalBlockId )
			);
			expect( gen.next( '111111' ).value ).toEqual(
				put( actions.deleteRSVP() )
			);
			expect( gen.next().value ).toEqual(
				call( [ wpDispatch( 'core/editor' ), 'removeBlocks' ], [ '111111' ] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );

	describe( 'setNonEventPostTypeEndDate', () => {
		it( 'shoud exit on non-events', () => {
			const gen = sagas.setNonEventPostTypeEndDate();

			expect( gen.next().value ).toEqual(
				call( sagas.isTribeEventPostType )
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
				call( sagas.isTribeEventPostType )
			);

			expect( gen.next( false ).value ).toEqual(
				take( [ types.INITIALIZE_RSVP ] )
			);
			expect( gen.next().value ).toEqual(
				select( selectors.getRSVPTempEndDateMoment )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( [ momentMock, 'clone' ] )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( [ momentMock, 'add' ], 100, 'years' )
			);
			expect( gen.next( momentMock ).value ).toEqual(
				call( sagas.createDates, momentMock.toDate() )
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
