/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';

describe( 'Reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the is selected', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsIsSelected( true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the is settings open', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsIsSettingsOpen( true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the is settings loading', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsIsSettingsLoading( true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the provider', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsProvider( 'provider' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the shared capacity', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsSharedCapacity( 99 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the temp shared capacity', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketsTempSharedCapacity( 99 ),
		) ).toMatchSnapshot();
	} );
} );
