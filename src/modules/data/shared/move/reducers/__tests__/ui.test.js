/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../posts';
import * as types from '../../types';

describe( 'Move UI reducer', () => {
	it( 'should show default', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );
	it( 'should fetch types', () => {
		expect(
			reducer( undefined, { type: types.SHOW_MODAL } ),
		).toMatchSnapshot();
	} );
	it( 'should fetch types with success', () => {
		expect(
			reducer( undefined, { type: types.HIDE_MODAL } ),
		).toMatchSnapshot();
	} );
} );
