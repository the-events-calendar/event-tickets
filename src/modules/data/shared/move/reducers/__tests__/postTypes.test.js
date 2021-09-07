/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../posts';
import * as types from '../../types';

describe( 'Move postTypes reducer', () => {
	it( 'should show default', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );
	it( 'should fetch types', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_TYPES } ),
		).toMatchSnapshot();
	} );
	it( 'should fetch types with success', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_TYPES_SUCCESS, data: { posts: { a: 1 } } } ),
		).toMatchSnapshot();
	} );
	it( 'should fetch types with error', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_TYPES_ERROR } ),
		).toMatchSnapshot();
	} );
} );
