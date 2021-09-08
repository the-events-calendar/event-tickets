/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../posts';
import * as actions from '../../actions';
import * as types from '../../types';

describe( 'Move posts reducer', () => {
	it( 'should show default', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );
	it( 'should reset to default', () => {
		expect( reducer( undefined, { type: types.RESET_MODAL_DATA } ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set data', () => {
		expect(
			reducer( undefined, actions.setModalData( { post_type: 'some' } ) ),
		).toMatchSnapshot();
	} );
	it( 'should fetch choices', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_CHOICES } ),
		).toMatchSnapshot();
	} );
	it( 'should fetch choices with success', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_CHOICES_SUCCESS, data: { posts: { a: 1 } } } ),
		).toMatchSnapshot();
	} );
	it( 'should fetch choices with error', () => {
		expect(
			reducer( undefined, { type: types.FETCH_POST_CHOICES_ERROR } ),
		).toMatchSnapshot();
	} );
} );
