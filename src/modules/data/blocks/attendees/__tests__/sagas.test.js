/**
 * External dependencies
 */
import { takeEvery, put, all } from 'redux-saga/effects';
import { cloneableGenerator } from 'redux-saga/utils';

/**
 * Internal Dependencies
 */
import * as types from '../types';
import { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import watchers, * as sagas from '../sagas';

describe( 'Attendees Block sagas', () => {
	describe( 'watchers', () => {
		it( 'should watch actions', () => {
			const gen = watchers();
			expect( gen.next().value ).toEqual(
				takeEvery( types.SET_ATTENDEES_INITIAL_STATE, sagas.setInitialState ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
	describe( 'setInitialState', () => {
		let action;
		beforeEach( () => {
			action = { payload: {
				get: jest.fn(
					( name, _default ) => DEFAULT_STATE[ name ] || _default,
				),
			} };
		} );

		it( 'should set initial state', () => {
			const gen = cloneableGenerator( sagas.setInitialState )( action );
			expect( gen.next().value ).toEqual(
				all( [
					put( actions.setTitle( action.payload.get( 'title', DEFAULT_STATE.title ) ) ),
					put( actions.setDisplayTitle(
						action.payload.get( 'displayTitle', DEFAULT_STATE.displayTitle ),
					) ),
					put( actions.setDisplaySubtitle(
						action.payload.get( 'displaySubtitle', DEFAULT_STATE.displaySubtitle ),
					) ),
				] ),
			);
			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
