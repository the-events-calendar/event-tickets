/**
 * External Dependencies
 */
import * as actions from '../actions';

describe( 'Move Actons', () => {
	const keys = Object.keys( actions );
	const payload = [ {}, 1, 2 ];

	keys.forEach( ( key ) => {
		test( key, () => {
			expect( actions[ key ]( ...payload ) ).toMatchSnapshot();
		} );
	} );
} );
