/**
 * External dependencies
 */
import { noop } from 'lodash';
import { call } from 'redux-saga/effects';

/**
 * Internal Dependencies
 */
import {
	globals,
	moment as momentUtil,
} from '@moderntribe/common/utils';
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

	describe( 'createWPEditorNotSavingChannel', () => {
		it( 'should create channel', () => {
			expect( sagas.createWPEditorNotSavingChannel() ).toMatchSnapshot();
		} );
	} );

	describe( 'createDates', () => {
		const date = '2018-01-01 00:00:00';
		it( 'should create dates when no format', () => {
			const gen = sagas.createDates( date );

			expect( gen.next().value ).toEqual(
				call( [ globals, 'tecDateSettings' ] ),
			);

			expect( gen.next( { datepickerFormat: false } ).value ).toEqual(
				call( momentUtil.toMoment, date ),
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDatabaseDate, {} ),
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {} ),
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toDatabaseTime, {} ),
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toTime, {} ),
			);

			expect( gen.next().done ).toEqual( true );
		} );
		it( 'should create dates with datepicker format', () => {
			const gen = sagas.createDates( date );

			expect( gen.next().value ).toEqual(
				call( [ globals, 'tecDateSettings' ] ),
			);

			expect( gen.next( { datepickerFormat: true } ).value ).toEqual(
				call( momentUtil.toMoment, date ),
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDatabaseDate, {} ),
			);

			expect( gen.next( {} ).value ).toEqual(
				call( momentUtil.toDate, {}, true ),
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toDatabaseTime, {} ),
			);

			expect( gen.next( date ).value ).toEqual(
				call( momentUtil.toTime, {} ),
			);

			expect( gen.next().done ).toEqual( true );
		} );
	} );
} );
