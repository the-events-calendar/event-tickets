/**
 * External dependencies
 */
import { takeEvery, put, call, select, all } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * Internal Dependencies
 */
import * as types from '../types';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';
import { moment as momentUtil } from '@moderntribe/common/utils';

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
				takeEvery( types.SET_RSVP_DETAILS, sagas.setRSVPDetails ),
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_RSVP_TEMP_DETAILS, sagas.setRSVPTempDetails ),
			);
			expect( gen.next().value ).toEqual(
				takeEvery( types.INITIALIZE_RSVP, sagas.initializeRSVP ),
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
				startDateObj: new Date( 'January 1, 2018' ),
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endDateObj: new Date( 'January 4, 2018' ),
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
					put( actions.setRSVPStartDateObj( new Date( 'January 1, 2018' ) ) ),
					put( actions.setRSVPStartTime( '12:34' ) ),
					put( actions.setRSVPEndDate( 'January 4, 2018' ) ),
					put( actions.setRSVPEndDateObj( new Date( 'January 4, 2018' ) ) ),
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
				tempStartDateObj: new Date( 'January 1, 2018' ),
				tempStartTime: '12:34',
				tempEndDate: 'January 4, 2018',
				tempEndDateObj: new Date( 'January 4, 2018' ),
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
					put( actions.setRSVPTempStartDateObj( new Date( 'January 1, 2018' ) ) ),
					put( actions.setRSVPTempStartTime( '12:34' ) ),
					put( actions.setRSVPTempEndDate( 'January 4, 2018' ) ),
					put( actions.setRSVPTempEndDateObj( new Date( 'January 4, 2018' ) ) ),
					put( actions.setRSVPTempEndTime( '23:32' ) ),
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
				startDateObj: new Date( 'January 1, 2018' ),
				startTime: '12:34',
				endDate: 'January 4, 2018',
				endDateObj: new Date( 'January 4, 2018' ),
				endTime: '23:32',
			};
			global.tribe = {
				events: {
					blocks: {
						datetime: {
							selectors: {
								getStart: jest.fn(),
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
				call( momentUtil.toMoment, state.startDate )
			);
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toDate, state.startDate )
			);
			expect( gen.next( state.startDate ).value ).toEqual(
				call( momentUtil.toTime24Hr, state.startDate )
			);
			expect( gen.next( state.startTime ).value ).toEqual(
				all( [
					put( actions.setRSVPTempStartDate( state.startDate ) ),
					put( actions.setRSVPTempStartDateObj( state.startDateObj ) ),
					put( actions.setRSVPTempStartTime( state.startTime ) ),
				] )
			)
			expect( gen.next().value ).toEqual(
				select( global.tribe.events.blocks.datetime.selectors.getStart )
			);
			expect( gen.next( state.endDate).value ).toEqual(
				call( momentUtil.toMoment, state.endDate )
			);
			expect( gen.next( state.endDate).value ).toEqual(
				call( momentUtil.toDate, state.endDate )
			);
			expect( gen.next( state.endDate).value ).toEqual(
				call( momentUtil.toTime24Hr, state.endDate )
			);
			expect( gen.next( state.endTime ).value ).toEqual(
				all( [
					put( actions.setRSVPTempEndDate( state.endDate ) ),
					put( actions.setRSVPTempEndDateObj( state.endDateObj ) ),
					put( actions.setRSVPTempEndTime( state.endTime ) ),
				] )
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
