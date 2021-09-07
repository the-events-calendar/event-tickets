/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../modal';
import * as actions from '../../actions';
import * as types from '../../types';

describe( 'Move modal reducer', () => {
	it( 'should show default', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );
	it( 'should reset to default', () => {
		expect( reducer( {}, { type: types.RESET_MODAL_DATA } ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set data', () => {
		expect(
			reducer( undefined, actions.setModalData( { post_type: 'some' } ) ),
		).toMatchSnapshot();
	} );
	it( 'should move ticket', () => {
		expect(
			reducer( undefined, { type: types.MOVE_TICKET } ),
		).toMatchSnapshot();
	} );
	it( 'should move ticket with success', () => {
		expect(
			reducer( undefined, { type: types.MOVE_TICKET_SUCCESS } ),
		).toMatchSnapshot();
	} );
	it( 'should move ticket with error', () => {
		expect(
			reducer( undefined, { type: types.MOVE_TICKET_ERROR } ),
		).toMatchSnapshot();
	} );
} );
