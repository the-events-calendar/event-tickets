/**
 * External dependencies
 */
import { takeEvery, put, call, select, all, fork, take } from 'redux-saga/effects';
import { cloneableGenerator, createMockTask } from 'redux-saga/utils';
import { noop } from 'lodash';

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
import * as sagas from '../sagas';

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

describe( 'Shared block sagas', () => {

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

	describe( 'createWPEditorSavingChannel', () => {
		it( 'should create channel', () => {
			expect( sagas.createWPEditorSavingChannel() ).toMatchSnapshot();
		} );
	} );
} );
